<?php

namespace App\TraitClass;

use Illuminate\Support\Facades\Redis;

trait PHPRedisTrait
{
    public $apiRedisKey = [
        'hot_tags' => 'api_hot_tags_list', //热门标签
        'home_lists' => 'api_section_cid-page:', //主页列表
        'register_did' => 'api_did_', //注册机器码
        'user_gold_video' => 'api_ugv_', //用户购买过金币对应hash
    ];

    public $redisExpiredTime = 3600*24;

    public function redis($name=null)
    {
        return Redis::connection($name)->client();
    }

    public function redisBatchDel($keys,$redis=null)
    {
        $redis = $redis ?? $this->redis();
        foreach ($keys as $key){
            $key = str_replace('laravel_database_','',$key);
            $redis->del($key);
        }
    }
}