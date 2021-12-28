<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends BaseModel
{
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $dateFormat = 'U';

    public static function addLog($str, $type = 'log')
    {
        $table = new self();
        $table->admin_id = admin('id');
        $table->admin_name = admin('nickname');
        //$table->ip = request()->getClientIp();
        $table->ip = $_SERVER['HTTP_X_REAL_IP'] ?? request()->getClientIp();
        $table->type = $type;
        $table->name = $str;
        $table->url = url(request()->path());//操作路径

        return $table->save();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public static function type($key = 'arr')
    {
        $arr = [
            'log' => '日志',
            'login' => '登录'
        ];
        if ($key === 'arr') {
            return $arr;
        }
        return $arr[$key]??'';
    }

    public function getTypeNameAttribute()
    {
        //值存在就更新

        return self::type($this->type);


    }

    public function setIpAttribute($ip)
    {
        if($ip){
            $this->attributes['ip'] = ip2long($ip);
        }
    }

    public function getIpAttribute($ip)
    {
        return $ip ? long2ip($ip) : $ip;
    }

}
