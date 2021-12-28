<?php

namespace App\TraitClass;

use App\Models\WhiteList;

trait WhiteListTrait
{
    public function whitelistPolice()
    {
        $ip = $_SERVER['HTTP_X_REAL_IP'] ?? \request()->getClientIp();
        //白名单
        $whiteList = WhiteList::query()
            ->where('status',1)
            ->where('type',1)
            ->pluck('ip')->toArray();
        if(!in_array($ip, $whiteList)){
            return false;
        }
        return true;
    }
}