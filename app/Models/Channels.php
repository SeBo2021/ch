<?php

namespace App\Models;

class Channels extends BaseModel
{
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $dateFormat = 'U';

    /**
     * 自动设置密码加密
     * @param $password
     */
    public function setPasswordAttribute($password)
    {
        //值存在就更新
        if ($password) {
            $this->attributes['password'] = bcrypt($password);
        }

    }

}