<?php

namespace App\Models;

class ChannelCpsTotal extends BaseModel
{
    protected $table = 'channel_cps';

    //子级
    public function childs()
    {
        return $this->hasMany(self::class, 'pid', 'id')->orderBy('id','asc');
    }
}