<?php


namespace App\Models;


class Carousel extends BaseModel
{
    public function category(){
        return $this->belongsTo(Category::class,'cid','id');
    }
}
