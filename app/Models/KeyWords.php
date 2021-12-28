<?php


namespace App\Models;


use Laravel\Scout\Searchable;

class KeyWords extends BaseModel
{
    use Searchable;

    protected $table = 'key_words';

    public function searchableAs()
    {
        return 'key_words_index';
    }

}