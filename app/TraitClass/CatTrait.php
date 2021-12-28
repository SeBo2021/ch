<?php

namespace App\TraitClass;

use App\Models\Category;

trait CatTrait
{
    public function getCats()
    {
        $topCat = Category::query()
            ->where('parent_id',2)
            ->where('is_checked',1)
            ->orderBy('sort')
            ->get(['id','name','sort'])
            ->toArray();
        $topCatIds = [];
        foreach ($topCat as $item)
        {
            $topCatIds[] = $item['id'];
        }
        if(!empty($topCatIds)){
            return Category::query()
                ->where('is_checked',1)
                ->whereIn('parent_id',$topCatIds)
                ->orderBy('sort')
                ->get(['id','name'])->toArray();
        }
        return [];
    }

    public function getCatName($cat)
    {
        $topCat = $this->getCats();
        $catArr = json_decode($cat, true);
        $name = '';
        $characters = '||';
        foreach ($topCat as $item)
        {
            if(in_array($item['id'],$catArr)){
                $name .= $item['name'].$characters;
            }
        }
        return rtrim($name,$characters);
    }
}