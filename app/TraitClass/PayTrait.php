<?php

namespace App\TraitClass;


use App\Jobs\ProcessMemberCard;
use App\Models\Gold;
use App\Models\MemberCard;
use App\Models\Order;
use App\Models\PayLog;
use App\Models\Recharge;
use App\Models\RechargeChannel;
use App\Models\User;
use App\Models\Video;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

trait PayTrait
{
    /**
     * 返回支付类型标识
     * @param string $flag
     * @return string
     */
    public static function getPayType($flag=''): string
    {
        $payTypes = [
            'DBS' => '1',
        ];
        return $payTypes[$flag]??'0';
    }

    /**
     * 生成订单号
     * @return string
     */
    public static function getPayNumber(): string
    {
        return 'JB'.time().rand(10000,99999);
    }

    /**
     * vip信息表
     * @param $cardId
     * @return Builder|Builder[]|Collection|Model|null
     */
    private function getVipInfo($cardId): Model|Collection|Builder|array|null
    {
        return MemberCard::query()->find($cardId)?->toArray();
    }

    /**
     * gold信息表
     * @param $Id
     * @return Builder|Builder[]|Collection|Model|null
     */
    private function getGoldInfo($Id): Model|Collection|Builder|array|null
    {
        return Gold::query()->find($Id)?->toArray();
    }

    /**
     * 视频信息
     * @param $goodsId
     * @return Model|Collection|Builder|array|null
     */
    private function getGoodsInfo($goodsId): Model|Collection|Builder|array|null
    {
        return Video::query()->find($goodsId)?->toArray();
    }

    /**
     * 处理视频购买
     * @param $goodsId
     * @return Model|Collection|Builder|array|null
     */
    private function buyVideo($goodsId): Model|Collection|Builder|array|null
    {
        // return Video::query()->find($goodsId)?->toArray();
        return [];
    }

    /**
     * 处理骚豆购买
     * @param $id
     * @param $uid
     * @return Model|Collection|Builder|array|null
     */
    private function buyGold($id,$uid): Model|Collection|Builder|array|null
    {
        $info = Gold::query()->find($id)?->toArray();
        User::query()->find($uid)->update(
            ['gold' =>DB::raw("gold + {$info['money']}") ]
        );
        return [];
    }

    /**
     * 处理vip购买
     * @param $goodsId
     * @param $uid
     * @return Model|Collection|Builder|array|null
     */
    private function buyVip($goodsId,$uid): Model|Collection|Builder|array|null
    {
        $cardInfo = MemberCard::query()
            ->find($goodsId,['id','value','real_value','expired_hours']);
        if($cardInfo->expired_hours > 0) {
            $expiredTime = $cardInfo->expired_hours * 3600 + time();
            $expiredAt = date('Y-m-d H:i:s',$expiredTime);
        }
        $user = User::query()->findOrFail($uid);
        $member_card_type = !empty($user->member_card_type) ? (array)$user->member_card_type : [];
        $member_card_type[] = $cardInfo->id;
        $updateMember = implode(',',$member_card_type);
        User::query()->find($uid)->update(['member_card_type' => $updateMember]);
        //队列执行
        if($cardInfo->expired_hours >= 0) {
            $job = new ProcessMemberCard($user->id,$cardInfo->id,($cardInfo->expired_hours?:10*365*34)*60*60);
            app(Dispatcher::class)->dispatchNow($job);
        }
        return [
            'expired_at' => $expiredAt??false
        ];
    }

    /**
     * 订单更新
     * @param $tradeNo
     * @param array $jsonResp
     * @param $userInfo
     * @throws Exception
     */
    private function orderUpdate($tradeNo,$jsonResp = []): void
    {
        $payModel = PayLog::query()->where(['number' => $tradeNo]);
        $payInfo = $payModel->firstOrFail();
        if ($payInfo->status == 1){
            return;
        }
        $payModel->update([
                'response_info' => json_encode($jsonResp),
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s', time()),
            ]
        );
        $orderModel = Order::query()->where(['id' => $payInfo->order_id??0]);
        $update = $orderModel->update([
            'status' => 1,
            'updated_at' => date('Y-m-d H:i:s', time()),
        ]);
        if (!$update) {
            throw new Exception('订单更新失败', -1);
        }
        $orderInfo = $orderModel->firstOrFail();
        $nowData = date('Y-m-d H:i:s',time());


        $payInfo = $payModel->firstOrFail();
        $payModel->update([
                'response_info' => json_encode($jsonResp),
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s', time()),
            ]
        );

        $method = match ($orderInfo->type??0) {
            1 => 'buyVip',
            2 => 'buyGold',
            3 => 'buyVideo',
        };
        $biz = $this->$method($orderInfo->type_id??0,$payInfo->uid);
        $chargeData = [
            'type' => $orderInfo->type??1,
            'uid' => $orderInfo->uid,
            'status' => 1,
            'amount' => $orderInfo->amount,
            'device_system' => $payInfo->device_system,
            'created_at' => $nowData,
            'updated_at' => $nowData,
        ];
        if ($expiredAt = $biz['expired_at']??false) {
            $chargeData['expired_at'] = $expiredAt;
        }
        Recharge::query()->create($chargeData);
    }

    /**
     * 得到支付信息
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function getPayEnv(): mixed
    {
        $payEnv = cache()->get('payEnv');
        if (!$payEnv) {
            $payEnv = RechargeChannel::query()
                ->where('status',1)
                ->get()?->toArray();
            cache()->set('payEnv',array_column($payEnv,null,'name'));
        }
        return $payEnv;
    }
}