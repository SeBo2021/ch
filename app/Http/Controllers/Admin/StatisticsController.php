<?php

namespace App\Http\Controllers\Admin;

use App\TraitClass\StatisticTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends BaseCurlController
{
    use StatisticTrait;
    //去掉公共模板
    public $commonBladePath = '';
    public $pageName = '统计图表';

    public function index()
    {
        $channels = DB::table('channels')->where('status',1)->pluck('name','id')->all();
        $channels = [ ''=>'全部',0 => '官方'] + $channels;
        return $this->display(['channels' => $channels]);
    }

    public function getList(Request $request)
    {
        $json = [];
        $channelId = $request->input('channel_id');
        $deviceSystem = $request->input('deviceSystem',0);
        //时间范围
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d',strtotime('-30 day'));
        $timeRange = $request->input('range_date', 0);
        if($timeRange > 0){
            $timeRangeArr = explode('~',$timeRange);
            $startDate = trim($timeRangeArr[0]);
            $endDate = trim($timeRangeArr[1]);
        }
        switch ($request->input('type','')){
            case 'totalStatistic':
                $fields = 'SUM(access) as total_access,
                SUM(hits) as total_hits,
                SUM(install) as total_install,
                SUM(register) as total_register,
                AVG(keep_day_rate) as avg_keep_day_rate,
                AVG(keep_week_rate) as avg_keep_week_rate,
                AVG(keep_month_rate) as avg_keep_month_rate';
                $queryBuild = DB::table('statistic_day')->select(DB::raw($fields));
                if($channelId!==null){
                    $queryBuild = $queryBuild->where('channel_id',$channelId);
                }

                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }

                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('at_time','>=',strtotime($startDate))
                        ->where('at_time','<=',strtotime($endDate));
                }

                $totalData = $queryBuild->orderByDesc('at_time')->limit(30)->get()[0];

                // 修正安装量与注册量
                $newData = $this->fixDataByUserTable($channelId, $deviceSystem, $timeRange, $startDate, $endDate);
                $totalData->total_install = $newData['newInstall'];
                $totalData->total_register = $newData['newRegister'];

                $json = [
                    'access' => $totalData->total_access,
                    'hits' => $totalData->total_hits,
                    'install' => $totalData->total_install,
                    'register' => $totalData->total_register,
                    'keep1AG' => round($totalData->avg_keep_day_rate,2) . '%',
                    'keep7AG' => round($totalData->avg_keep_week_rate,2).'%',
                    'keep30AG' => round($totalData->avg_keep_month_rate,2).'%',
                ];
                break;
            case 'increment':
                $fields = 'SUM(access) as total_access,
                SUM(hits) as total_hits,
                SUM(install) as total_install,
                SUM(register) as total_register,
                SUM(keep_day_users) as total_keep_day_users,
                SUM(keep_week_users) as total_keep_week_users,
                SUM(keep_month_users) as total_keep_month_users,
                SUM(keep_day_rate) as total_keep_day_rate,
                SUM(keep_week_rate) as total_keep_week_rate,
                SUM(keep_month_rate) as total_keep_month_rate';
                $queryBuild = DB::table('statistic_day')->select('at_time',DB::raw($fields));
                if($channelId!==null){
                    $queryBuild = $queryBuild->where('channel_id',$channelId);
                }
                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }
                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('at_time','>=',strtotime($startDate))
                        ->where('at_time','<=',strtotime($endDate));
                }
                $totalData = $queryBuild->groupBy('at_time')->orderByDesc('at_time')->limit(15)->get();
                $totalData = array_reverse($totalData->toArray());
                foreach ($totalData as $item){
                    $json['x'][] = date('Y-m-d',$item->at_time);
                    $json['series']['total_access'][] = $item->total_access;
                    $json['series']['total_hits'][] = $item->total_hits;
                    $json['series']['total_install'][] = $item->total_install;
                    $json['series']['total_register'][] = $item->total_register;
                    $json['series']['total_keep_day_users'][] = $item->total_keep_day_users;
                    $json['series']['total_keep_week_users'][] = $item->total_keep_week_users;
                    $json['series']['total_keep_month_users'][] = $item->total_keep_month_users;
                    $json['series']['total_keep_day_rate'][] = $item->total_keep_day_rate;
                    $json['series']['total_keep_week_rate'][] = $item->total_keep_week_rate;
                    $json['series']['total_keep_month_rate'][] = $item->total_keep_month_rate;
                }
                break;
            case 'activeUsers':
                $queryBuild = DB::table('users_day')->select('at_time',DB::raw('count(uid) as users'));
                if($channelId!==null){
                    $queryBuild = $queryBuild->where('channel_id',$channelId);
                }
                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }
                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('at_time','>=',strtotime($startDate))
                        ->where('at_time','<=',strtotime($endDate));
                }
                $activeUsers = $queryBuild->groupBy(['at_time'])->orderByDesc('at_time')->take(15)->get();
                $activeUsers = array_reverse($activeUsers->toArray());
                foreach ($activeUsers as $activeUser){
                    $json['x'][] = date('Y-m-d',$activeUser->at_time);
                    $json['y'][] = $activeUser->users;
                }
                break;
            case 'recharge':
                $queryBuild = DB::table('recharge')->select(DB::raw('DATE_FORMAT(recharge.created_at,"%Y-%m-%d") days'),DB::raw('SUM(amount)/100 as money'));
                if($channelId!==null){
                    $queryBuild = $queryBuild
                        ->join('users','recharge.uid','=','users.id')
                        ->where('users.channel_id',$channelId);
                }
                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }
                if($timeRange != 0){
                    $queryBuild = $queryBuild->whereBetween('recharge.created_at',[$startDate,$endDate]);
                }
                $items = $queryBuild->groupBy('days')->orderByDesc('days')->limit(15)->get();
                $items = array_reverse($items->toArray());
                $X = [];
                $Y = [];
                foreach ($items as $item){
                    $X[] = $item->days;
                    $Y[] = $item->money;
                }
                $json = ['x' => $X,'y' => $Y];
                break;
            case 'users':
                // 修正安装量与注册量
                $json = $this->fixDataByUserTable($channelId, $deviceSystem, $timeRange, $startDate, $endDate,true);

                $systemName = [
                    0 => '其它',
                    1 => '苹果(IOS)',
                    2 => '安卓(Android)',
                ];
                foreach ($json as &$item){
                    $item->name = $systemName[$item->device_system];
                }
                break;
            case 'IPDistribution':
                $queryBuild = DB::table('login_log');
                if($channelId!==null){
                    $queryBuild = $queryBuild
                        ->select('area','ip','users.channel_id','login_log.device_system',DB::raw('count(distinct ip) as ips'),DB::raw('json_extract(area,"$[1]") as province'))
                        ->join('users','login_log.uid','=','users.id')
                        ->where('users.channel_id',$channelId);
                }else{
                    $queryBuild = $queryBuild
                    ->select('area','ip','device_system',DB::raw('count(distinct ip) as ips'),DB::raw('json_extract(area,"$[1]") as province'));
                }

                $items = $queryBuild
                    ->groupBy(['province','device_system'])
                    ->distinct()
                    ->get();

                $system = [0=>'all',1=>'ios',2=>'android'];
                $json['android'] = [];
                $json['ios'] = [];
                $ips = [];
                foreach ($items as $item){
                    $jsonArea = @json_decode($item->area,true);
                    if(isset($jsonArea[1])){
                        $json[$system[$item->device_system]][]  = ['name' => $jsonArea[1],'value' => $item->ips];
                        $ips[] = $item->ips;
                    }
                }
                $json['min'] = !empty($ips) ? min($ips) : 0;
                $json['max'] = !empty($ips) ? max($ips) : 0;
                break;
        }
        return response()->json($json);
    }
}