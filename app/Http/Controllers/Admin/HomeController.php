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

            case 'summaryCpsOrCpa':
                if($channelType==2){ //cps
                    $cpsData = DB::table('channel_cps')->where('channel_id',$channelId)->orderByDesc('date_at')->take(15)->get(['total_recharge_amount','orders','date_at']);
                    foreach ($cpsData as $item){
                        $json['x'][] = $item->date_at;
                        $json['amount'][] = $item->total_recharge_amount;
                        $json['order'][] = $item->orders;
                    }

                }else{ //cpa
                    $cpaData = DB::connection('origin_mysql')->table('statistic_day_deduction')
                        ->where('channel_id',$channelId)
                        ->orderByDesc('at_time')->take(15)
                        ->get(['install','at_time']);
                    foreach ($cpaData as $item){
                        $json['x'][] = date('Y-m-d',$item->at_time);
                        $json['y'][] = $item->install;
                    }
                }

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
