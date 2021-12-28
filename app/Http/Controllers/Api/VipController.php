<?php

namespace App\Http\Controllers\Api;

use App\TraitClass\MemberCardTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipController extends \App\Http\Controllers\Controller
{
    use MemberCardTrait;
    /*public function openVip(Request $request)
    {
        $user = $request->user();
        if(!empty($user)){
            $res=[
                'member_card_type' => $user->member_card_type ?? 0,
                'saol_gold' => $user->gold ?? 0,
            ];
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }*/

    public function memberCards(Request $request)
    {
        $memberCard = DB::table('member_card')
            ->where('status',1)
            ->orderBy('sort')
            ->get(['id','name','sort','bg_img','remark','value','rights','hours','real_value','status','name_day'])
            ->toArray();
        foreach ($memberCard as &$item)
        {
            $item = (array)$item;
            $rights = $this->numToRights($item['rights']);
            $rights_list = [];
            $registerTime = strtotime($request->user()->created_at);
            $nowTime = time();
            foreach ($rights as $right)
            {
                $rights_list[] = $this->cardRights[$right];
                if(($item['hours']>0) && ($item['real_value']>0)){
                    if($nowTime < ($registerTime+$item['hours']*3600)){
                        $item['valid_period'] = $registerTime+$item['hours']*3600-$nowTime;
                    }else{
                        $item['valid_period'] = 0;
                        $item['real_value'] = 0;
                    }
                }
            }
            if ($item['name_day'] && ($rights_list[0]['id'] == 1)) {
                $rights_list[0]['name'] = $item['name_day'];
            }
            $item['rights_list'] = $rights_list;
            unset($item['rights']);
        }
        $res['list'] = $memberCard;
        return response()->json([
            'state'=>0,
            'data'=>$res
        ]);
    }

    public function gold()
    {
        $gold = DB::table('gold')
            ->where('status',1)
            ->orderBy('sort')
            ->get(['id','money']);
        return response()->json([
            'state'=>0,
            'data'=>$gold
        ]);
    }
}