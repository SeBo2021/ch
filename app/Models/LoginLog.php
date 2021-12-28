<?php


namespace App\Models;


class LoginLog extends BaseModel
{
    protected $table = 'login_log';

    protected $dateFormat = 'Y-m-d H:i:s';

    /*public function member()
    {
        return $this->belongsTo(Member::class,'mid','id');
    }*/
}