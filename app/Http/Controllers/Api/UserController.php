<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Jobs\ProcessStatistic;
use App\Models\User;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\IpTrait;
use App\TraitClass\LoginTrait;
use App\TraitClass\MemberCardTrait;
use App\TraitClass\SmsTrait;
use App\TraitClass\VideoTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;

class UserController extends Controller
{
    use MemberCardTrait,SmsTrait,LoginTrait,VideoTrait;

    public function set(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $onlyFields = ['nickname','email','sex','phone_number','avatar'];
            $setData = [];
            foreach ($params as $key=>$value){
                if(in_array($key,$onlyFields)){
                    $setData[$key] = $value;
                }
            }
            $user = $request->user();
            if(!empty($setData)){
                $state = User::query()->where('id',$user->id)->update($setData);
                $userInfo = User::query()->find($user->id,$onlyFields);
                $res = $userInfo;
                $userInfo['avatar'] += 0;
                if($state>0){
                    $msg = '设置成功';
                }else{
                    $msg = '重复设置或操作过快';
                }
                $state = 0;
                return response()->json([
                    'state'=>$state,
                    'msg'=>$msg,
                    'data'=>$res
                ]);
            }
        }
        return [];
    }

    public function extendInfo(Request $request): JsonResponse|array
    {
        $user = $request->user();
        if(!empty($user)){
            $types = explode(',',$user->member_card_type);
            $memberCardTypeId = !empty($types) ? $types[0] : 0;
            $member_card = [
                'name' => '未开通',
                'expired_time' => '',
                'is_vip' => 0,
                'vip_expired' => '',
            ];
            if($memberCardTypeId>0){
                $memberCardInfo = DB::table('member_card')->find($memberCardTypeId,['id','name','value','expired_hours']);
                $expired_at = DB::table('orders')
                    ->where('type',1)
                    ->where('type_id',$memberCardTypeId)
                    ->where('status',1)
                    ->orderBy('id')
                    ->value('expired_at');
                $expired_time = $memberCardInfo->expired_hours>0 ? $expired_at : '';

                $calc = ($user->vip_expired?:0) - (time()-($user->vip_start_last?:time()));
                $isVip = 0;
                if ($calc >= 0) {
                    $isVip = 1;
                }
                if (in_array(8,$types)) {
                    $vipDay = -1;
                } else {
                    $vipDay = ceil((($calc>0)?$calc:0)/(24*60*60));
                }
                $member_card = [
                    'name' => $memberCardInfo->name,
                    'expired_time' => $expired_time,
                    'is_vip' => $isVip,
                    'vip_expired' => date('Y-m-d',time() + $calc),
                    'vip_day' => $vipDay,
                ];
            }

            $res=[
                'member_card' => $member_card,
                // 'saol_gold' => $user->gold ? number_format($user->gold/100, 2, '.') : 0,
                'saol_gold' => $user->gold ?:0,
            ] ;
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    /*public function videoList(Request $request)
    {

        $res = [];
        $state = -1;
        $msg = '缺少参数';
        if(isset($request->params)){
            $params = Crypt::decryptString($request->params);
            $params = json_decode($params,true);
            $page = $params['page'];
            $state = 0;
            $onlyFields = ['id','name','cid','uid','title','url','gold','duration','hls_url','dash_url','type','cover_img','views','updated_at'];
            $user = $request->user();
            $paginator = UserVideo::query()
                ->where('uid',$user->id)
                ->where('status',1)
                ->orderBy('updated_at','desc')
                ->simplePaginate(10,$onlyFields,'userVideo',$page);
            $paginatorArr = $paginator->toArray();
            if(!empty($paginatorArr)){
                $res['list'] = $paginatorArr['data'];
                $res['domain'] = env('APP_URL');
                $res['hasMorePages'] = $paginator->hasMorePages();
            }
        }
        return response()->json([
            'state'=>$state,
            'msg'=>$msg,
            'data'=>$res
        ]);
    }*/
    public function billing(Request $request): JsonResponse|array
    {
        if(isset($request->params)) {
            $params = ApiParamsTrait::parse($request->params);
            $page = $params['page'] ?? 1;
            $perPage = 16;
            $fields = ['id','type','type_id','amount','updated_at'];
            $uid = $request->user()->id;
            $paginator = DB::table('orders')
                ->where('uid',$uid)
                ->where('status','>',0)
                ->orderByDesc('id')->simplePaginate($perPage,$fields,'commentLists',$page);
            $orders = $paginator->items();
            $memberCard = DB::table('member_card')->pluck('name','id')->all();
            foreach ($orders as &$order){
                if($order->type==1){
                    $order->name =  $memberCard[$order->type_id];
                }elseif ($order->type==2){
                    $order->name = '骚豆充值';
                }
                // $order->amount = number_format($order->amount/100,2);
                unset($order->type);
                unset($order->type_id);
            }
            $res['list'] = $orders;
            $res['hasMorePages'] = $paginator->hasMorePages();
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    public function billingClear(Request $request): JsonResponse
    {
        $user = $request->user();
        DB::table('orders')->where('uid',$user->id)->update(['status' => -1]);
        return response()->json([
            'state'=>0,
            'msg'=> '账单已清除'
        ]);
    }

    public function myShare(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $page = $params['page'] ?? 1;
            $perPage = 10;
            if(isset($params['pageSize']) && ($params['pageSize']<10)){
                $perPage = $params['pageSize'];
            }
            $user = $request->user();
            $userField = ['id','avatar','nickname','updated_at'];
            $paginator = DB::table('users')
                ->where('pid',$user->id)
                ->simplePaginate($perPage,$userField,'myShare',$page);
            $res['hasMorePages'] = $paginator->hasMorePages();
            $pageData = $paginator->toArray();
            $res['list'] = $pageData['data'];
            foreach ($res['list'] as &$item){
                $dateTime = explode(' ',$item->updated_at);
                $item->date = $dateTime[0] ?? '';
                $item->avatar = $item->avatar>0 ? (int)$item->avatar : rand(1,13);
                unset($item->updated_at);
            }
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    public function myCollect(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $perPage = 10;
            $res = [];
            $params = ApiParamsTrait::parse($request->params);
            if(isset($params['delete']) && $params['delete']==1){
                $vid = $params['vid'] ?? [];
                if(!empty($vid)){
                    $user = $request->user();
                    $collectIds = DB::table('view_record')
                        ->where('uid',$user->id)
                        ->whereIn('vid',$vid)
                        ->pluck('id')
                        ->all();
                    if(!empty($collectIds)){
                        DB::table('view_record')->whereIn('id',$collectIds)->update(['is_collect'=>0]);
                    }
                }
                return response()->json([
                    'state'=>0,
                    'msg' => '删除成功',
                    'data'=>[]
                ]);
            }
            $page = $params['page'] ?? 1;
            if(isset($params['pageSize']) && ($params['pageSize']<$perPage)){
                $perPage = $params['pageSize'];
            }
            $user = $request->user();
            $paginator = DB::table('video')
                ->join('view_record','video.id','=','view_record.vid')
                ->where('view_record.uid',$user->id)
                ->where('view_record.is_collect',1)
                ->simplePaginate($perPage,$this->videoFields,'myCollect',$page);
            //路径处理
            $res['list'] = $this->handleVideoItems($paginator->items());
            //时长转秒
            $res['list'] = self::transferSeconds($res['list']);
            $res['hasMorePages'] = $paginator->hasMorePages();
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    public function viewHistory(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $perPage = 10;
            $res = [];
            $params = ApiParamsTrait::parse($request->params);
            if(isset($params['delete']) && $params['delete']==1){
                $vid = $params['vid'] ?? [];
                if(!empty($vid)){
                    $user = $request->user();
                    $collectIds = DB::table('view_history')
                        ->where('uid',$user->id)
                        ->whereIn('vid',$vid)
                        ->pluck('id')
                        ->all();
                    if(!empty($collectIds)){
                        DB::table('view_history')->whereIn('id',$collectIds)->delete();
                    }
                }
                return response()->json([
                    'state'=>0,
                    'msg' => '删除成功',
                    'data'=>[]
                ]);
            }
            $page = $params['page'] ?? 1;
            if(isset($params['pageSize']) && ($params['pageSize']<10)){
                $perPage = $params['pageSize'];
            }
            $user = $request->user();
            $paginator = DB::table('video')
                ->join('view_history','video.id','=','view_history.vid')
                ->where('view_history.uid',$user->id)
                ->simplePaginate($perPage,$this->videoFields,'viewHistory',$page);
            //路径处理
            $res['list'] = $this->handleVideoItems($paginator->items());
            //时长转秒
            $res['list'] = self::transferSeconds($res['list']);
            $res['hasMorePages'] = $paginator->hasMorePages();
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    public static function transferSeconds($lists)
    {
        foreach ($lists as &$list) {
            if(isset($list->duration) && $list->duration>0){
                $His = explode(':',$list->duration);
                if(!empty($His)){
                    switch (array_key_last($His)){
                        case 0:
                            $His[0]+=0;
                            $list->duration_seconds = $His[0];
                            break;
                        case 1:
                            $His[0]+=0;
                            $His[1]+=0;
                            $list->duration_seconds = $His[0]*60 + $His[1];
                            break;
                        case 2:
                            $His[0]+=0;
                            $His[1]+=0;
                            $His[2]+=0;
                            $list->duration_seconds = $His[0] * 60 * 60 + $His[1] * 60 + $His[2];
                            break;
                    }
                }
            }
        }
        return $lists;
    }

    public function bindInviteCode(Request $request)
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'code' => 'required|string',
            ])->validated();
            $code = $validated['code'] ?? '';
            if(!empty($code)){
                $user = $request->user();
                if($user->pid>0){
                    return response()->json(['state'=>-1, 'msg'=>'不能重复绑定']);
                }
                $pid = User::query()->where('promotion_code',$code)->value('id');
                if($pid==$user->id){
                    return response()->json(['state'=>-1, 'msg'=>'不能绑定自己']);
                }
                User::query()->where('id',$user->id)->update(['pid' => $pid]);
                return response()->json(['state'=>0, 'msg'=>'绑定成功']);
            }
        }
        return [];
    }

    public function getAreaNum(Request $request)
    {
        return response()->json(['state'=>0, 'data'=>$this->getSmsAreaNum()]);
    }

    public function sendSmsCode(Request $request)
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'phone' => 'required|integer',
                'areaNum' => 'required|integer',
            ])->validated();

            $ip = IpTrait::getRealIp();

            $smsId = DB::table('sms_codes')
                ->where('phone',$validated['phone'])
                ->where('status',0)
                ->where('created_at', '>', date("Y-m-d H:i:s", strtotime("-3 minute")))
                ->take(1)
                ->value('id');
            if($smsId > 0){
                return response()->json(['state'=>-1, 'msg'=>'请不要重复发送！']);
            }

            $times = DB::table('sms_codes')
                ->where('phone',$validated['phone'])
                ->whereDate('created_at',date('Y-m-d'))
                ->count();
            if($times > 20){
                return response()->json(['state'=>-1, 'msg'=>'您当天累计已发送20次！']);
            }

            $code = mt_rand(100000, 999999);
            $type = $validated['areaNum']!='86' ? 2 : 1;
            switch ($type){
                case 1:
                    $this->sendChinaSmsCode($validated['phone'], $code);
                    break;
                case 2:
                    $this->sendInternationalSmsCode($validated['areaNum'],$validated['phone'], $code);
                    break;
            }

            $date = date('Y-m-d H:i:s');
            $smsData = [
                'phone' => $validated['phone'],
                'area_number' => $validated['areaNum'],
                'code' => $code,
                'ip' => $ip,
                'status' => 0,
                'created_at' => $date,
                'updated_at' => $date,
            ];
            $getId = DB::table('sms_codes')->insertGetId($smsData);
            if($getId >0){
                return response()->json(['state'=>0, 'msg'=>'发送成功']);
            }
        }
        return [];
    }

    /**
     * @throws ValidationException
     */
    public function bindPhone(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'phone' => 'required|integer',
                'code' => 'required|integer',
            ])->validated();
            $phoneUserId = User::query()->where('phone_number',$validated['phone'])->value('id');
            if($phoneUserId > 0){
                return response()->json(['state'=>-1, 'msg'=>'该手机号已绑定过']);
            }
            $smsCode = $this->validateSmsCode($validated['phone'],$validated['code']);
            Log::debug('bindPhone===',[$validated]);
            Log::debug('validateSmsCode===',[$smsCode]);
            if(!$smsCode){
                return response()->json(['state'=>-1, 'msg'=>'短信验证码不正确']);
            }
            $user = $request->user();
            DB::beginTransaction();
            DB::table('sms_codes')->where('id',$smsCode->id)->update(['status'=>1]);
            DB::table('users')->where('id',$user->id)->update(['phone_number'=>$validated['phone'],'area_number'=>$smsCode->area_number]);
            DB::commit();
            //统计注册量
            ProcessStatistic::dispatchAfterResponse($user);
            return response()->json(['state'=>0, 'msg'=>'绑定成功']);
        }
        return [];
    }

    public function findADByPhone(Request $request)
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'phone' => 'required|integer',
                'code' => 'required|integer',
            ])->validated();

            //====
            $smsCode = $this->validateSmsCode($validated['phone'],$validated['code']);
            Log::debug('findADByPhone===',[$validated]);
            Log::debug('validateSmsCode===',[$smsCode]);
            if(!$smsCode){
                return response()->json(['state'=>-1, 'msg'=>'短信验证码不正确']);
            }
            DB::table('sms_codes')->where('id',$smsCode->id)->update(['status'=>1]);
            //====
            $requestUserInfo = $request->user();
            $user = User::query()
                ->where('phone_number',$validated['phone'])
                ->where('area_number',$smsCode->area_number)
                ->first();
            if(!$user){
                return response()->json(['state'=>-1, 'msg'=>'该手机没有绑定过帐号,无法找回']);
            }
            //同一账号的情况
            if($requestUserInfo->account == $user->account){
                return response()->json(['state'=>-1, 'msg'=>'找回账号与此账号是同一账号']);
            }
            $user->status =1;
            $user->save();
            //清除当前用户token和账号禁用
            Token::query()->where('name',$requestUserInfo->account)->delete();
            User::query()->where('id',$requestUserInfo->id)->update(['status' => 0]);

            $tokenResult = $user->createToken($user->account,['check-user']);
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addDays();
            $token->save();
            $user = $user->only($this->loginUserFields);
            if(isset($user['avatar'])){
                $user['avatar'] += 0;
            }
            $user['token'] = $tokenResult->accessToken;
            $user['token_type'] = 'Bearer';
            $user['expires_at'] = Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString();
            $user['expires_at_timestamp'] = strtotime($user['expires_at']);
            //生成用户专有的客服链接
            $user = $this->generateChatUrl($user);

            return response()->json(['state'=>0, 'data'=>$user, 'msg'=>'账号找回成功']);
        }
        return [];
    }

}