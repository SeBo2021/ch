<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class ChannelCpsTotal extends BaseModel
{
    protected $table = 'channel_cps';

    //子级
    /*public function childs()
    {
        $fields1 = 'SUM(install) as total_install,
                SUM(orders) as orders,
                SUM(total_recharge_amount) as total_recharge_amount,
                SUM(total_amount) as total_amount,
                SUM(share_amount) as share_amount,
                SUM(total_orders) as total_orders';
        return $this->hasMany('App\Models\ChannelCpsTotal', 'pid', 'channel_id')
            ->select('id','pid','name','channel_id','share_ratio',DB::raw($fields1))->groupBy('channel_id')
            ->orderBy('channel_id','asc');
    }*/
    //子级
    /*public function childs()
    {
        return $this->hasMany(self::class, 'pid', 'channel_id');
    }*/
}