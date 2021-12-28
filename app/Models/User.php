<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\TraitClass\SearchScopeTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SearchScopeTrait;


    protected $guarded = [];

//    protected $table = 'users';

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
