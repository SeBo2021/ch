<?php

namespace App\TraitClass;

trait MemberCardTrait
{
    public $cardRights = [
        1 => [
            'id' => 1,
            'icon' => 1,
            'name' => '观看VIP影片'
        ],
        2 => [
            'id' => 2,
            'icon' => 10,
            'name' => '专属客服'
        ],
        3 => [
            'id' => 3,
            'icon' => 4,
            'name' => '会员专有标识'
        ],
        4 => [
            'id' => 4,
            'icon' => 3,
            'name' => '会员福利群'
        ],
        5 => [
            'id' => 5,
            'icon' => 7,
            'name' => '评论特权'
        ],
        6 => [
            'id' => 6,
            'icon' => 2,
            'name' => '高清视频'
        ],
        7 => [
            'id' => 7,
            'icon' => 9,
            'name' => '收藏特权'
        ],
        8 => [
            'id' => 8,
            'icon' => 5,
            'name' => '空降女友抽奖'
        ],
        9 => [
            'id' => 9,
            'icon' => 6,
            'name' => '祼聊外围'
        ],
        10 => [
            'id' => 10,
            'icon' => 8,
            'name' => '骚豆影片免费'
        ],
    ];

    public function numToRights($num)
    {
        $rights = [];
        foreach ($this->cardRights as $right)
        {
            $pos = $right['id']-1;
            if((($num >> $pos) & 1) == 1){
                $rights[] = $right['id'];
            }
        }
        return $rights;
    }

    public function getRightsName($num)
    {
        $ids = $this->numToRights($num);
        $name = '';
        $char = '||';
        foreach ($ids as $id)
        {
            $name .= $this->cardRights[$id]['name'] . $char;
        }
        return rtrim($name,$char);
    }

    public function binTypeToNum($rights)
    {
        $num = 0;
        foreach ($rights as $right)
        {
            $num += pow(2,$right-1);
        }
        return $num;
    }

}