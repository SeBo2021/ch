<?php

namespace App\TraitClass;

use App\Models\Ad;
use App\Models\AdSet;

trait AdTrait
{
    public static function weightGet($flag='')
    {
        $ads = Ad::query()
            ->where('name',$flag)
            ->where('status',1)
            ->get(['id','name','weight','title','img','position','url','play_url','type','status'])
            ->toArray();
        $one = [];

        foreach ($ads as $ad){
            $weight = $ad['weight']; //权重值要设置在一到10的范围
            $randValue = rand(1,10);
            if($randValue <= $weight){
                $one = $ad;
                break;
            }
        }
        if(!empty($ads)){
            if(empty($one)){ //若未命中权重概率,则随机取一
                $key = array_rand($ads);
                $one = $ads[$key];
            }
            $domain = env('APP_URL');
            $one['img'] = $domain . $one['img'];
            return [$one];
        }
        return [];
    }

    public static function get($flag='',$groupByPosition=false)
    {
        $ads = Ad::query()
            ->where('name',$flag)
            ->where('status',1)
            ->orderBy('sort')
            ->get(['id','sort','name','title','img','position','url','play_url','type','status'])
            ->toArray();
        if($groupByPosition){
            $newAds = [];
            $domain = env('APP_URL');
            foreach ($ads as $ad){
                $ad['img'] = $domain . $ad['img'];
                $newAds[$ad['position']][]= $ad;
            }
            $ads = $newAds;
        }
        return !empty($ads) ? $ads : [];
    }

    public static function insertAds($data, $flag='', $usePage=false, $page=1, $perPage=6)
    {
        $adSet = cache()->get('ad_set');
        if (!$adSet) {
            $adSet = array_column(AdSet::get()->toArray(),null,'flag');
            cache()->set('ad_set',$adSet);
        }
        $res = $data;
        $rawPos = $adSet[$flag]['position'];
        if ($rawPos == 0) {
            $ads = self::get($flag,$usePage);
            foreach ($res as $k=>$v){
                $tmpK = $usePage ? (($page-1) * $perPage + $k) : $k;
                $res[$k]['ad_list'] = $ads[$tmpK] ?? [];
            }
            return $res;
        } else {
            $ads = self::get($flag,false);
        }
        $position = explode(':',$rawPos);
        $adCount = count($ads);
        if ($position[1]??false) {
            $position = rand($position[0],$position[1]);
        } else {
            // 不启用分组
            $position = $position[0];
        }
        $counter = 0;
        unset($k,$v);
        foreach ($res as $k=>$v){
            $cur = ($page-1) * $perPage + $k + 1;
            if ($position != 0) {
                if (($cur % $position == 0) && ($cur != 0)) {
                    $adsKey = $counter%$adCount;
                    $counter++;
                    $res[$k]['ad_list'] = [];
                    $tmpAd = $ads[$adsKey]??[];
                    if ($tmpAd) {
                        $res[$k]['ad_list'] = [$tmpAd];
                    }
                } else {
                    $res[$k]['ad_list'] = [];
                }
                continue;
            }
            $tmpK = $usePage ? $cur : $k;
            $res[$k]['ad_list'] = $ads[$tmpK] ?? [];
        }
        return $res;
    }

}
