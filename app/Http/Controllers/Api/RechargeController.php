<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessMemberCard;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\GoldTrait;
use App\TraitClass\MemberCardTrait;
use App\TraitClass\OrderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RechargeController extends \App\Http\Controllers\Controller
{
    use OrderTrait, MemberCardTrait, GoldTrait;

    public function methods()
    {
        $methods =  DB::table('recharge_type')->where('status',1)->get(['id','name','sort','icon','channel']);
        return response()->json([
            'state'=>0,
            'data'=>$methods
        ]);
    }

    public function submit(Request $request)
    {
        if(isset($request->params)) {
            $params = ApiParamsTrait::parse($request->params);
            Validator::make($params, [
                'amount' => 'required|integer',
                'id' => 'required|integer',
                'rechargeType' => 'required|integer',
                'orderType' => 'required|integer',
            ])->validate();
            //todo 加入扣量统计表
            /*$amount = $params['amount'] * ($this->goldUnit);
            $rechargeType = $params['rechargeType'];
            $orderType = $params['orderType'];
            $orderTypeId = $params['id'] ?? 0;
            $user = $request->user();
            $nowData = date('Y-m-d H:i:s');
            $insertOrderData = [
                'number' => $this->generateOrderNumber(),
                'type' => $orderType,
                'type_id' => $orderTypeId,
                'uid' => $user->id,
                'status' => 1, //todo 审核
                'amount' => $amount,
                'created_at' => $nowData,
                'updated_at' => $nowData,
            ];
            $insertRechargeData = [
                'type' => $rechargeType,
                'uid' => $user->id,
                'status' => 1,
                'amount' => $amount,
                'device_system' => $user->device_system,
                'created_at' => $nowData,
                'updated_at' => $nowData,
            ];
            if($orderType == 1){ //会员卡
                $cardInfo = DB::table('member_card')->find($orderTypeId,['id','value','real_value','expired_hours']);
                if(($cardInfo->value!=$params['amount']) && ($cardInfo->real_value!=$params['amount'])){
                    return response()->json(['state'=>-1, 'msg'=>'数据错误']);
                }
                if($cardInfo->expired_hours > 0) {
                    $expiredTime = $cardInfo->expired_hours * 3600 + time();
                    $insertOrderData['expired_at'] = date('Y-m-d H:i:s',$expiredTime);
                }
                $member_card_type = !empty($user->member_card_type) ? (array)$user->member_card_type : [];
                $member_card_type[] = $orderTypeId;
                $updateMember = implode(',',$member_card_type);
                DB::beginTransaction();
                    $order_id = DB::table('orders')->insertGetId($insertOrderData);
                    $insertRechargeData['order_id'] = $order_id;
                    DB::table('recharge')->insert($insertRechargeData);
                    //todo 审核
                    DB::table('users')->where('id',$user->id)->update(['member_card_type' => $updateMember]);
                DB::commit();
                //队列执行
                if($cardInfo->expired_hours > 0) {
                    $job = new ProcessMemberCard($user->id,$cardInfo->id);
                    $this->dispatch($job->delay(now()->addHours($cardInfo->expired_hours)));
                    //ProcessMemberCard::dispatch($user->id,$cardInfo->id)->delay(now()->addHours($cardInfo->expired_hours));
                }
            }
            elseif ($orderType == 2){ //骚豆
                DB::beginTransaction();
                $order_id = DB::table('orders')->insertGetId($insertOrderData);
                $insertRechargeData['order_id'] = $order_id;
                DB::table('recharge')->insert($insertRechargeData);
                //todo 审核
                $gold = $user->gold+$amount;
                DB::table('users')->where('id',$user->id)->update(['gold' => $gold]);
                DB::commit();
            }*/
            //$updateData = ['id'=>$user->id,'member_card_type'=>];

            return response()->json([
                'state'=>0,
                'msg'=>'提交成功',
                'data'=>[]
            ]);
        }
        return [];
    }
}