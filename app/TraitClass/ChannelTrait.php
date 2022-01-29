<?php

namespace App\TraitClass;

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
}