<?php

namespace App\Models;

class Users extends BaseModel
{
    protected $connection = 'origin_mysql';

    protected $table = 'users';
}