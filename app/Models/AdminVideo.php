<?php


namespace App\Models;

class AdminVideo extends BaseModel
{
    protected $table = 'video';

    public function category(){
        return $this->belongsTo(Category::class,'cid','id');
    }


}
