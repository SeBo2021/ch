<?php

namespace App\TraitClass;

use Illuminate\Support\Facades\DB;

trait ChannelTrait
{
    public $deviceSystems = [
        0 => 'default',
        1 => '苹果',
        2 => '安卓',
    ];

    public $bindPhoneNumSelectData = [
        '' => [
            'id' =>'',
            'name' => '全部',
        ],
        0 => [
            'id' => 0,
            'name' => '未绑定',
        ],
        1 => [
            'id' => 1,
            'name' => '已绑定',
        ],
    ];

    public function getChannelSelectData($all=null): array
    {
        $queryBuild = DB::table('channels');
        if($all===null){
            $queryBuild = $queryBuild->where('status',1);
        }
        $items = [ ''=>'全部',0 => '官方'] + $queryBuild->pluck('name','id')->all();
        $lists = [];
        foreach ($items as $key => $value){
            $lists[$key] = [
                'id' => $key,
                'name' => $value,
            ];
        }
        return $lists;
    }
}