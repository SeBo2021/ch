<?php


namespace App\Models;


use Laravel\Scout\Searchable;

class UserVideo extends BaseModel
{
    use Searchable;

    protected $table = 'user_video';

    public function category(){
        return $this->belongsTo(Category::class,'cid','id');
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'user_video_index';
    }

    /**
     * 获取模型的可搜索数据。
     *
     * @return array
     */
    /*public function toSearchableArray()
    {
        $array = $this->toArray();

        // 自定义数组...

        return $array;
    }*/

    //指定id
    /*public function getScoutKey()
    {
        return $this->id;
    }

    public function getScoutKeyName()
    {
        return 'id';
    }*/

}
