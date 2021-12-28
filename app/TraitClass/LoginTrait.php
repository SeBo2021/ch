<?php

namespace App\TraitClass;

trait LoginTrait
{

    public $loginUserFields = ['id','account','channel_id','nickname','device_system','phone_number','promotion_code','avatar','sex','gold','balance','long_vedio_times','area_number'];

    public $loginRules = [
        'type' => 'required|integer|between:1,2',
        'did' => 'required|string',
        'dev' => 'required',
        'env' => 'required',
        'name' => 'nullable|string',
        'test' => 'nullable|boolean',
    ];

    public $createNickNames = [
        '十六分的音符',
        '你所見即我',
        '一稍皎月',
        '今夜有星星',
        '一莺时',
        '我怎么可能不可爱',
        '关掉月亮的音量键',
        '乏味万千',
        '一枕星梦河',
        '池鱼',
        '安妮的心动录',
        '屋顶上的拾荒人',
        '何为美人',
    ];

    public function generateChatUrl(Array $user): array
    {
        $queryParam = http_build_query([
            'account' => $user['account'],
            'nickname' => $user['nickname'],
            'id' => $user['id']
        ]);
        $user['kf_url'] = 'https://vm.daneviolda.com/1wyai8j871j3z054rryao48w7g?'.$queryParam;
        return $user;
    }
}