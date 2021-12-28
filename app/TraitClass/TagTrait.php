<?php

namespace App\TraitClass;

use App\Models\Tag;

trait TagTrait
{

    public function getTagData()
    {
        return Tag::query()->get(['id','name'])->toArray();
    }

    public function getTagName($tag)
    {
        $tagData = $this->getTagData();
        $tagArr = json_decode($tag, true);
        $name = '';
        $characters = '||';
        foreach ($tagData as $item)
        {
            if(in_array($item['id'],$tagArr)){
                $name .= $item['name'].$characters;
            }
        }
        return rtrim($name,$characters);
    }
}