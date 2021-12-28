<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLogin;
use App\Models\User;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\IpTrait;
use App\TraitClass\LoginTrait;
use App\TraitClass\PHPRedisTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    use LoginTrait,PHPRedisTrait;
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);

        /*$user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = 1;
        $user->save();*/

        return response()->json([
            'state' => 0,
            'message' => '注册成功'
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $params = ApiParamsTrait::parse($request->params);
        Log::debug('login_request_params_info===',[$params]);//参数日志
        $validated = Validator::make($params,$this->loginRules)->validated();
        //短时间内禁止同一设备注册多个账号
        $key = $this->apiRedisKey['register_did'].$validated['did'];
        if($this->redis()->get($key)){
            Log::debug('register_did===',[$validated['did']]);//参数日志
            return response()->json(['state' => -1, 'msg' => '重复注册']);
        }
        $deviceInfo = !is_string($validated['dev']) ? json_encode($validated['dev']) : $validated['dev'] ;
        $appInfo = !is_string($validated['env']) ? json_encode($validated['env']) : $validated['env'] ;

        $ip = IpTrait::getRealIp();
        $test = $validated['test'] ?? false;

        $user = new User();
        $member = $user::query()->where('did',$validated['did'])->where('status',1)->orderByDesc('created_at')->first($this->loginUserFields);
        $loginType = !$member ? 1 : 2;
        switch ($loginType){
            case 1:
                //创建新用户
                $user->did = $validated['did'];
                $user->last_did = $validated['did'];
                $user->create_ip = $ip;
                $user->last_ip = $ip;
                $user->gold = 0;
                $user->balance = 0;
                $user->sex = 0;
                //分配默认相关设置
                $configData = config_cache('app');
                $user->long_vedio_times = $configData['free_view_long_video_times'] ?? 0;
                $user->avatar = rand(1,13);
                if(!isset($_SERVER['HTTP_USER_AGENT'])){
                    return response()->json(['state' => -1, 'msg' => '非法设备!']);
                }

                $user->device_system = 0;
                if(strpos($deviceInfo.'', 'androidId')){
                    $user->device_system = 2;
                }else if(strpos($deviceInfo.'', 'ios')){
                    $user->device_system = 1;
                }
                $user->device_info = $deviceInfo;
                $user->app_info = $appInfo ?? '{}';
                DB::beginTransaction();
                //
                $nickNames = $this->createNickNames;
                $randNickName = $this->createNickNames[array_rand($nickNames)];
                $user->account = 'ID-'.Str::random(6);
                try {   //先偿试队列
                    $user->save();
                    $randNickName = $randNickName.'-'.$user->id;
                    $account = $user->account . '-' .$user->id;
                    //更新账号
                    $user->account = $account;
                    $user->nickname = $randNickName;
                    $user->password = $user->account;
                    $user->save();
                    //
                    $this->redis()->setex($key,60,1);
                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    Log::error('createUser===' . $e->getMessage());
                }

                $login_info = $user->only($this->loginUserFields);
                //Log::debug('===login_info===',$login_info);
                break;
            case 2: //再次登录
                if(!$member){
                    return response()->json(['state' => -1, 'msg' => '用户不存在或被禁用!']);
                }
                $login_info = $member->only($this->loginUserFields);
                //授权登录验证用户名密码
                if(!Auth::attempt(['account'=>$member->account, 'password'=>$member->account])){
                    return response()->json(['state'=>-1,'msg' => 'Unauthorized'], 401);
                }
                $user = $request->user();
                break;
        }
        $login_info['avatar'] += 0;
        //记录登录日志
        $login_log_data = [
            'ip'=>$ip,
            'uid'=>$login_info['id'],
            'promotion_code'=>$login_info['promotion_code'],
            'type'=>$loginType,
            'account'=>$login_info['account'],
            'nickname'=>$login_info['nickname'],
            'device_info'=> $deviceInfo,
            'source_info'=> $_SERVER['HTTP_USER_AGENT'],
            'device_system'=> $login_info['device_system'] ?? 0,
        ];
        ProcessLogin::dispatchAfterResponse($login_log_data);

        Token::query()->where('name',$login_info['account'])->delete();
        //重新分配token
        $tokenResult = $user->createToken($login_info['account'],['check-user']);
        $token = $tokenResult->token;
        $token->expires_at = !$test ? Carbon::now()->addDays() : Carbon::now()->addMinutes(3);
        $token->save();

        Log::debug('login_result_info===',[$login_info]);
        //返回的token信息
        $login_info['token'] = $tokenResult->accessToken;
        $login_info['token_type'] = 'Bearer';
        $login_info['expires_at'] = Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
        $login_info['expires_at_timestamp'] = strtotime($login_info['expires_at']);
        $login_info['phone_number'] = strval($login_info['phone_number']);
        //生成用户专有的客服链接
        $login_info = $this->generateChatUrl($login_info);

        return response()->json([
            'state'=>0,
            'data'=>$login_info
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'state' => 0,
            'msg' => '登出成功'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

}
