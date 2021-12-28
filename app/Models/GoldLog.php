<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\TraitClass\SearchScopeTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class GoldLog extends Authenticatable
{
    protected $table = 'gold_log';
    protected $fillable = ['uid','goods_id','cash','before_cash','goods_info','use_type','device_system','created_at','updated_at'];
}
