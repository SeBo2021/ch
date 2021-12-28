<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends AuthModel
{
    protected $dates = [
        'deleted_at',
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

    public function getNameAttribute()
    {
        //值存在就更新
       return $this->nickname;
    }

    public function setLastIpAttribute($ip)
    {
        if($ip){
            $this->attributes['last_ip'] = ip2long($ip);
        }
    }

    public function getLastIpAttribute($ip)
    {
        return long2ip($ip);
    }

}
