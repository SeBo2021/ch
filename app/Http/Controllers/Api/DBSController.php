<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PayLog;
use App\Services\Pay;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\PayTrait;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DBSController extends Controller implements Pay
{
    use PayTrait;
    use ApiParamsTrait;

    /**
     * 大白鲨鱼支付动作
     * @param Request $request
     * @return mixed
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function pay(Request $request): mixed
    {
        // TODO: Implement pay() method.
        $params = ApiParamsTrait::parse($request->params??'');
        Validator::make($params, [
            'pay_id' => 'required|string',
            'type' => [
                'required',
                'string',
                Rule::in(['wechat','alipay','union','quickUnion','fixedAlipay','fixedWechat','usdt','unionCard','fixedIosPay','fixedIosWechat']),
            ],
        ])->validate();
        Log::info('dbs_pay_params===',[$params]);//参数日志
        try {
            $payEnv = SELF::getPayEnv();
            $payInfo = PayLog::query()->find($params['pay_id']);
            if (!$payInfo) {
                throw new Exception("记录不存在");
            }
            $orderInfo = Order::query()->find($payInfo['order_id']);
            if (!$orderInfo) {
                throw new Exception("订单不存在");
            }
            $mercId = $payEnv['DBS']['merchant_id'];
            $notifyUrl = env('APP_URL') . $payEnv['DBS']['notify_url'];
            $input = [
                'mercId' => $mercId,
                'tradeNo' => strval($payInfo->number),
                'type' => $params['type'],
                'money' => strval($orderInfo->amount),
                'notifyUrl' => $notifyUrl,
                'time' => strval(time().'000'),
                // 'mode' => 'sdk',
                'sign' => $this->sign($mercId, $orderInfo->amount,$notifyUrl,$payInfo->number,$params['type'],$payEnv['DBS']['secret']),
                // 'payload' => //选填。⽬前仅⽀持过滤赔付渠道, 传 1 ,则过滤赔付渠道
                'info' => [
                    'playerId'=>strval($request->user()->id),
                    'playerIp'=>strval($request->user()->last_ip),
                    'deviceId'=>strval($request->user()->did),
                    'deviceType'=>match (intval($request->user()->device_system)){
                            1 => 'ios',
                            default => 'android',
                    },
                ]
            ];
            Log::info('dbs_third_params===',[$input]);//三方参数日志
            $response = (new Client([
                'headers' => ['Content-Type' => 'application/json']
            ]))->post($payEnv['DBS']['pay_url'], [
                'body'=>json_encode($input)
            ])->getBody();
            Log::info('dbs_third_response===',[$response]);//三方响应日志
            $resJson = json_decode($response,true);
            if ($resJson['code'] == 200) {
                $return = $this->format(0, $resJson, '取出成功');
            } else {
                $return = $this->format($resJson['code'], [], $resJson['err']);
            }
        } catch (Exception $e) {
            $return = $this->format($e->getCode(), [], $e->getMessage());
        }
        return response()->json($return);
    }

    /**
     * 订单回调
     * @param Request $request
     * @return mixed
     */
    public function callback(Request $request): mixed
    {
        // TODO: Implement callback() method.
        $jsonResp = $request->all();
        Log::info('dbs_pay_callback===',[$jsonResp]);//三方返回参数日志
        try {
            $mercId = $jsonResp['mercId'] ?? 0;
            if ($mercId != (SELF::getPayEnv()['DBS']['merchant_id']??'')) {
                return 'success';
            }
            $signPass = $this->sign(
                $jsonResp['code'],
                $jsonResp['mercId'],
                $jsonResp['oid'],
                $jsonResp['payMoney'],
                $jsonResp['tradeNo'],
                SELF::getPayEnv()['DBS']['secret']??'',
                $jsonResp['sign'],
            );

            if (!$signPass) {
                // 签名验证不通过
                throw new Exception('签名验证不通过', -1);
            }
            // 记录支付信息
            DB::beginTransaction();
            $this->orderUpdate($jsonResp['tradeNo'],$jsonResp);
            DB::commit();
            $return = 'success';
        } catch (Exception $e) {
            Log::info('dbs_error_callback===',['code'=>$e->getCode(),'msg'=>$e->getMessage()]);//三方返回参数日志
            DB::rollBack();
            $return = 'failure';
        }
        return response($return);
    }

    /**
     * 通过金额获取可用的支付方式
     * @param Request $request
     * @return mixed
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function method(Request $request): mixed
    {
        // TODO: Implement method() method.
        $params = ApiParamsTrait::parse($request->params);
        Validator::make($params, [
            'money' => 'required|string',
        ])->validate();
        Log::info('dbs_method_params===',[$params]);//三方参数日志
        $mercId = SELF::getPayEnv()['DBS']['merchant_id'];
        $isArray = stripos($params['money'],',');
        if ($isArray) {
            $money = array_map(function ($d){
                return strval($d);
            },explode(',',strval($params['money'])));
        } else {
            $money = [strval($params['money'])];
        }
        $input = [
            'mercId' => $mercId,
            'money' => $money,
            'time' => strval(time().'000'),
            // 'mode' => 'sdk',
            'sign' => $this->sign($mercId, $params['money'],SELF::getPayEnv()['DBS']['secret']),
        ];
        Log::info('dbs_method_request===',[$input]);//三方参数日志
        $response = (new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]))->post(SELF::getPayEnv()['DBS']['other_url'], [
            'body'=>json_encode($input)
        ])->getBody();
        Log::info('dbs_method_response===',[$response]);//三方响应日志
        if ($isArray) {
            $msg = json_decode($response)->msg;
        } else {
            $msg = json_decode($response)->msg[0]?->types;
        }
        $return = $this->format(0,$msg, '取出成功');
        return response()->json($return);
    }
    /**
     * 签名算法
     * @param string $mercId
     * @param string $money
     * @param string $notifyUrl
     * @param string $tradeNo
     * @param string $type
     * @param string $appSecret
     * @param string $check
     * @return mixed
     */
    private function sign($mercId = "", $money = "", $notifyUrl = "", $tradeNo = "", $type = "", $appSecret = "", $check = ""): mixed
    {
        $origin = sprintf("%s%s%s%s%s%s", $mercId, $money, $notifyUrl, $tradeNo, $type, $appSecret);
        $crypt = md5($origin);
        if ($check) {
            return $crypt == $check;
        }
        return $crypt;
    }
}