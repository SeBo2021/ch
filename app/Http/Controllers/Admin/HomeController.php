<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
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
        $adminAccount = admin('account');
        $channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',$adminAccount)->first();
        $channelId = $channelInfo ? $channelInfo->id : 0;
        $channelType = $channelInfo ? $channelInfo->type : 0;
        switch ($request->input('type','')){
            case 'data_overview':
                /*if($channelType != 2){

                }*/
                $queryBuild = DB::connection('origin_mysql')->table('statistic_day_deduction')->select(DB::raw('SUM(install) as total_install'));

                $totalData = $queryBuild->orderByDesc('at_time')->limit(30)->get()[0];

                $json = [
                    'total_' => $totalData->total_access,
                    'hits' => $totalData->total_hits,
                    'install' => $totalData->total_install,
                ];
                break;

            case 'activeUsers':
                $queryBuild = DB::connection('origin_mysql')->table('users_day')->select('at_time',DB::raw('count(uid) as users'));
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

        }
        return response()->json($json);
    }

    public function home(){
        return $this->display();
    }
    public function map($type,Request $request){
        $this->setViewPath($type.'Map');
        return $this->display();
    }
}
