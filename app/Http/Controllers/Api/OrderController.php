<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberCard;
use App\Models\Order;
use App\Models\PayLog;
use App\Models\Video;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\PayTrait;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiParamsTrait;
    use PayTrait;

    /**
     * 订单创建接口
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function create(Request $request): mixed
    {
        // TODO: Implement pay() method.
        $params = ApiParamsTrait::parse($request->params ?? '');
        Validator::make($params, [
            'type' => [
                'required',
                'string',
                Rule::in(['1', '2','3']),
            ],
            'goods_id' => 'required|string',
            'time' => 'required|string',
        ])->validate();
        Log::info('order_create_params===',[$params]);//参数日志
        $goodsMethod = match ($params['type']) {
            '1' => 'getVipInfo',
            '2' => 'getGoldInfo',
            '3' => 'getGoodsInfo',
        };
        $goodsInfo = $this->$goodsMethod($params['goods_id']);
        $now = date('Y-m-d H:i:s', time());
        try {
            $number = self::getPayNumber();
            DB::beginTransaction();
            // 创建订单
            $order = Order::query()->create([
                'remark' => json_encode($goodsInfo),
                'number' => $number,
                'type' => $params['type'],
                'type_id' => $params['goods_id'],
                'amount' => $goodsInfo[match ($params['type']) {
                    '1' => 'value',
                    '2' => 'money',
                    '3' => 'gold',
                }],
                'uid' => $request->user()->id,
                'status' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            // 准备支付记录
            $pay = PayLog::query()->create([
                'order_id' => $order->id,
                'number' => $number,
                'request_info' => json_encode($params),
                'goods_info' => $params['goods_id'],
                'uid' => $request->user()->id,
                'status' => 0,
                'device_system' => $request->user()->device_system??1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::commit();
            $return = $this->format(0, ['pay_id' => $pay->id,'order_id'=>$order->id], '取出成功');
        } catch (Exception $e) {
            DB::rollBack();
            $return = $this->format($e->getCode(), [], $e->getMessage());
        }

        return response()->json($return);
    }

    /**
     * 订单查询接口
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function query(Request $request): JsonResponse
    {
        $params = ApiParamsTrait::parse($request->params ?? '');
        Validator::make($params, [
            'order_id' => 'required|string',
        ])->validate();
        try {
            $order = Order::query()->findOrFail($params['order_id']);
            $return = $this->format(0,$order,"取出成功");
        } catch (Exception $e){
            $return = $this->format(-1,new \stdClass,"取出失败");
        }
        return response()->json($return);
    }

    /**
     * 得到支付单号
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws Exception
     */
    public function orderPay(Request $request): JsonResponse
    {
        $params = ApiParamsTrait::parse($request->params ?? '');
        Validator::make($params, [
            'order_id' => 'required|string',
            'time' => 'required|string',
        ])->validate();
        Log::info('order_pay_params===',[$params]);//参数日志
        try {
            $order = Order::query()->find($params['order_id'])?->toArray();
            if (!$order) {
                throw new Exception('订单不存在', -1);
            }
            if (1 == $order['status']) {
                throw new Exception('订单已经支付', -2);
            }
            $payLog = PayLog::query()->orderBy('id','desc')
                ->where([
                    'order_id'=>$params['order_id'],
                    'status'=>'0',
                    ])->first()?->toArray();
            if ($payLog) {
                $payId = $payLog['id'];
            } else {
                $now = date('Y-m-d H:i:s', time());
                $payNew = PayLog::query()->create([
                    'order_id' => $order['id'],
                    'request_info' => json_encode($params),
                    'goods_info' => $order['type_id'],
                    'number' => self::getPayNumber(),
                    'uid' => $request->user()->id,
                    'status' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $payId = $payNew['id'];
            }
            $return = $this->format(0, ['pay_id' => $payId], '取出成功');
        } catch (Exception $e) {
            $return = $this->format($e->getCode(), [], $e->getMessage());
        }
        return response()->json($return);
    }
}