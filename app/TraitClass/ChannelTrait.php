<?php

namespace App\TraitClass;

use App\Models\Users;
use Illuminate\Support\Facades\DB;

trait ChannelTrait
{
    //顶级渠道
    public function getTopChannels($type)
    {
        $res = DB::connection('origin_mysql')->table('channels')
            ->where('status',1)
            ->where('type',$type)
            ->where('pid',0)
            ->get(['id','name']);
        $data = $this->uiService->allDataArr('请选择渠道(一级)');
        foreach ($res as $item) {
            $data[$item->id] = [
                'id' => $item->id,
                'name' => $item->name,
            ];
        }
        return $data;
    }

    public function getAllChannels($type)
    {
        $res = DB::connection('origin_mysql')->table('channels')
            ->where('status',1)
            ->where('type',$type)
            ->get(['id','name']);
        $data = $this->uiService->allDataArr('全部');
        foreach ($res as $item) {
            $data[$item->id] = [
                'id' => $item->id,
                'name' => $item->name,
            ];
        }
        return $data;
    }

    public function getActiveViews($date_at=null): array
    {
        /*$build = Users::query()
            ->select('channel_id',DB::raw('SUM(IF(long_vedio_times<3,1,0)) as active_views'));
        if($date_at!==null){
            $dateArr = explode('~',$date_at);
            $build = $build->whereBetween('created_at',[trim($dateArr[0]).' 00:00:00',trim($dateArr[1]).' 23:59:59']);
        }
        return $build->groupBy('channel_id')
            ->pluck('active_views','channel_id')->all();*/

        $build = DB::connection('origin_mysql')->table('users_day')
            ->join('users', 'users.id', '=', 'users_day.uid')
            ->select('users_day.channel_id',DB::raw('count(users_day.uid) as active_views'));
        if($date_at!==null){
            $dateArr = explode('~',$date_at);
            $build = $build->whereBetween('users.created_at',[trim($dateArr[0]).' 00:00:00',trim($dateArr[1]).' 23:59:59']);
        }
        return $build->groupBy('users_day.channel_id')
            ->pluck('active_views','users_day.channel_id')->all();

    }
}