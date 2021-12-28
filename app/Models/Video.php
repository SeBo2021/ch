<?php


namespace App\Models;


use Illuminate\Support\Facades\Log;
use Laravel\Scout\Searchable;

class Video extends BaseModel
{
    use Searchable;

    protected $table = 'video';

    protected $mapping = [
        'properties' => [
            'name' => [
                'type' => 'text',
            ],
            'title' => [
                'type' => 'text',
            ],
        ]
    ];

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'video_index';
    }

    /**
     * 获取模型的可搜索数据。
     *
     * @return array
     */
    public function toSearchableArray()
    {
        //$array = $this->toArray();
        //'video.id','name','sync','title','url','gold','duration','type','cover_img','views','updated_at'
        // 自定义数组...
        //$array = $this->only(['id','name','sync','title','url','gold','duration','type','cover_img','views','updated_at']);

        //Log::debug('===toSearchableArray===',$this->toArray());
        return $this->toArray();
        //return $this->only(['id','name','sync','title','url','gold','duration','type','cover_img','views','updated_at']);
    }

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
