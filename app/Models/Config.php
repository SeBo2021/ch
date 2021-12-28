<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends BaseModel
{
    //
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $dateFormat = 'U';
}
