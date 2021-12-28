<?php

namespace App\Http\Controllers\ChannelApi;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticController extends \App\Http\Controllers\Controller
{

    public function index(Request $request)
    {
        $json = [];
        $code = $request->input('code','');
        $deviceSystem = $request->input('deviceSystem',0);
        $defaultEndDate = date('Y-m-d');
        $defaultStartDate = date('Y-m-d',strtotime('-30 day'));
        $timeRange = $request->input('timeRange',$defaultStartDate . '~'. $defaultEndDate);
        //$timeRange = '2021-10-18 16:21:17 ~ 2021-10-20 16:21:17';
        $one = DB::table('channels')->where('number',$code)->first(['id','deduction','name']);
        if(!$one){
            $json['msg'] = '参数错误';
            return response()->json($json);
        }
        $ChannelID = $one->id;
        $deduction = $one->deduction ? (1-$one->deduction/10000) : 1;
        if($timeRange != 0){
            $timeRangeArr = explode('~',$timeRange);
            $startDate = trim($timeRangeArr[0]);
            $endDate = trim($timeRangeArr[1]);
        }

        switch ($request->input('type')){
            case 'totalStatistic':
                $fields = 'SUM(access) as total_access,
                SUM(hits) as total_hits,
                SUM(install) as total_install,
                SUM(register) as total_register,device_system';
                $queryBuild = DB::table('statistic_day_deduction')->select(DB::raw($fields));
                $queryBuild = $queryBuild->where('channel_id',$ChannelID);

                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('at_time','>=',strtotime($startDate))
                        ->where('at_time','<=',strtotime($endDate));
                }
                $queryBuild = $queryBuild->groupBy(['channel_id','device_system']);
                $totalData = $queryBuild->orderByDesc('at_time')->limit(200)->get()->toArray();

                $totalArr = [];
                foreach ($totalData as $item){
                    $totalArr[$item->device_system] = [
                        'access' => round($item->total_access/100),
                        'hits' => round($item->total_hits/100),
                        'install' => round($item->total_install/100),
                        'register' => round($item->total_register/100)
                    ];
                }
                //Log::debug('test-statistic======',$totalArr);
                if( $deviceSystem>0 ){
                    $json = $totalArr[$deviceSystem];
                }else{
                    $iosAccess = $totalArr[1]['access'] ?? 0;
                    $androidAccess = $totalArr[2]['access'] ?? 0;
                    $iosHits = $totalArr[1]['hits'] ?? 0;
                    $androidHits = $totalArr[2]['hits'] ?? 0;
                    $iosInstall = $totalArr[1]['install'] ?? 0;
                    $androidInstall = $totalArr[2]['install'] ?? 0;
                    $iosRegister = $totalArr[1]['register'] ?? 0;
                    $androidRegister = $totalArr[2]['register'] ?? 0;
                    $json = !empty($totalArr) ? [
                        'access' => $iosAccess + $androidAccess,
                        'hits' => $iosHits + $androidHits,
                        'install' => $iosInstall + $androidInstall,
                        'register' => $iosRegister + $androidRegister
                    ] : [
                        'access' => 0,
                        'hits' => 0,
                        'install' => 0,
                        'register' => 0
                    ];
                }
                $json['channel_name'] = $one->name;
                break;
            case 'increment':
                $fields = 'SUM(access) as total_access,
                SUM(hits) as total_hits,
                SUM(install) as total_install,
                SUM(register) as total_register';
                $queryBuild = DB::table('statistic_day_deduction')->select('at_time',DB::raw($fields));
                $queryBuild = $queryBuild->where('channel_id',$ChannelID);
                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }
                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('at_time','>=',strtotime($startDate))
                        ->where('at_time','<=',strtotime($endDate));
                }
                $totalData = $queryBuild->groupBy(['at_time','channel_id'])->orderByDesc('at_time')->limit(15)->get();
                $totalData = array_reverse($totalData->toArray());
                if(!empty($totalData)){
                    foreach ($totalData as $item){
                        $json['x'][] = date('Y-m-d',$item->at_time);
                        $json['series']['total_access'][] = round($item->total_access/100);
                        $json['series']['total_hits'][] = round($item->total_hits/100);
                        $json['series']['total_install'][] = round($item->total_install/100);
                        $json['series']['total_register'][] = round($item->total_register/100);
                    }
                }else{
                    $json['x'][] = '';
                    $json['series']['total_access'][] = '';
                    $json['series']['total_hits'][] = '';
                    $json['series']['total_install'][] = '';
                    $json['series']['total_register'][] = '';
                }

                break;
            case 'activeUsers':
                $queryBuild = DB::table('statistic_users_day_deduction')->select('at_time',DB::raw('SUM(users) as users_num'));
                $queryBuild = $queryBuild->where('channel_id',$ChannelID);
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
                    $json['y'][] = round($activeUser->users_num/100) ;
                }
                break;
            case 'recharge':
                $queryBuild = DB::table('recharge')
                    ->select(DB::raw('DATE_FORMAT(recharge.created_at,"%Y-%m-%d") days'),DB::raw('SUM(recharge.amount)/100 as money'))
                    ->join('users','recharge.uid','=','users.id')
                    ->where('users.channel_id',$ChannelID);
                if( $deviceSystem>0 ){
                    $queryBuild = $queryBuild->where('device_system',$deviceSystem);
                }
                if($timeRange != 0){
                    $queryBuild = $queryBuild
                        ->where('recharge.created_at','>=',strtotime($startDate))
                        ->where('recharge.created_at','<=',strtotime($endDate));
                }
                $items = $queryBuild->groupBy('days')->orderByDesc('days')->limit(15)->get();
                $items = array_reverse($items->toArray());
                $X = [];
                $Y = [];
                foreach ($items as $item){
                    $X[] = $item->days;
                    $Y[] = round($item->money * $deduction);
                }
                $json = ['x' => $X,'y' => $Y];
                break;
            case 'users':
                $json = DB::table('statistic_day_deduction')
                    ->select('device_system',DB::raw('SUM(install) as value'))
                    ->where('channel_id',$ChannelID)
                    ->where('device_system','>',0)
                    ->groupBy(['device_system'])
                    ->get();
                $systemName = [
                    0 => '其它',
                    1 => '苹果(IOS)',
                    2 => '安卓(Android)',
                ];
                foreach ($json as &$item){
                    $item->name = $systemName[$item->device_system];
                    $item->value = round($item->value / 100);
                }
                break;
            case 'IPDistribution':
                $items = DB::table('login_log')
                    ->select('area','ip','users.channel_id','login_log.device_system',DB::raw('count(distinct ip) as ips'),DB::raw('json_extract(area,"$[1]") as province'))
                    ->join('users','login_log.uid','=','users.id')
                    ->where('users.channel_id',$ChannelID)
                    ->groupBy(['province','login_log.device_system'])
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