<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseCurlController
{
    //去掉公共模板
    public $commonBladePath = '';
    public $pageName = '首页';

    /**
     * 首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     */
    public function index(){
        return $this->display();
    }

    public function getList(Request $request)
    {
        $json = [];
        $channelId = $request->input('channel_id',0);
        $channelType = $request->input('channel_type',1);
        switch ($request->input('type','')){
            case 'dataOverview':
                if($channelType==2){ //cps
                    $queryBuild = DB::table('channel_cps')->where('channel_id',$channelId);
                    $totalAmount = $queryBuild->sum('total_recharge_amount');
                    $monthAmount = $queryBuild->whereDate('date_at','>=',date('Y-m-01'))->sum('total_recharge_amount');
                    $todayAmount = $queryBuild->whereDate('date_at',date('Y-m-d'))->sum('total_recharge_amount');
                    $monthOrders = $queryBuild->whereDate('date_at','>=',date('Y-m-01'))->sum('orders');
                    $todayOrders = $queryBuild->whereDate('date_at',date('Y-m-d'))->sum('orders');
                    $json = [
                        'total_amount' => $totalAmount,
                        'month_amount' => $monthAmount,
                        'today_amount' => $todayAmount,
                        'month_orders' => $monthOrders,
                        'today_orders' => $todayOrders,
                    ];
                }else{ //cpa
                    $queryBuild = DB::connection('origin_mysql')->table('statistic_day_deduction')->where('channel_id',$channelId);
                    $totalDownloads = $queryBuild->sum('install');
                    $currentMonthBeginTime = strtotime(date('Y-m-01 00:00:00'));
                    $TodayBeginTime = strtotime(date('Y-m-d 00:00:00'));
                    $monthDownloads = $queryBuild->where('at_time','>=',$currentMonthBeginTime)->sum('install');
                    $todayDownloads = $queryBuild->where('at_time','>=',$TodayBeginTime)->sum('install');
                    $json = [
                        'total_downloads' => $totalDownloads,
                        'month_downloads' => $monthDownloads,
                        'today_downloads' => $todayDownloads,
                    ];
                }
                break;

            case 'summaryDownloads':
                /*$queryBuild = DB::connection('origin_mysql')->table('users_day')->select('at_time',DB::raw('count(uid) as users'));
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
                }*/

                break;

        }
        return response()->json($json);
    }

    public function home(){
        $channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',admin('account'))->first();
        $channel_id = $channelInfo ? $channelInfo->id : 0;
        return $this->display(['channel_id' => $channel_id,'channel_type' =>$channelInfo ? $channelInfo->type : 0]);
    }
    public function map($type,Request $request){
        $this->setViewPath($type.'Map');
        return $this->display();
    }
}
