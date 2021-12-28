<?php

namespace App\Jobs;

use App\Models\LoginLog;
use App\Models\User;
use App\TraitClass\StatisticTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\Random;
use Zhuzhichao\IpLocationZh\Ip;
use Illuminate\Support\Str;

class ProcessLogin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StatisticTrait;

    /**
     * 任务尝试次数
     *
     * @var int
     */
    public $tries = 3;

    //跳跃式延迟执行
    public $backoff = [60,180];

    public $loginLogData=[];

    public $code = '';


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($loginLogData)
    {
        //
        $this->code = $loginLogData['promotion_code'];
        $this->device_system = $loginLogData['device_system'];
        unset($loginLogData['promotion_code']);
        $this->loginLogData = $loginLogData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // todo IP地区数据处理,优化
        //记录登录日志
        $area = Ip::find($this->loginLogData['ip']);
        $areaJson = json_encode($area,JSON_UNESCAPED_UNICODE);
        $this->loginLogData['area'] = $areaJson;
        LoginLog::query()->create($this->loginLogData);

        //增加登录次数
        $uid = $this->loginLogData['uid'];
        User::query()->where('id',$uid)->increment('login_numbers');
        //生成邀请码、更新手机平台、绑定渠道
        $this->updateUserInfo();
    }

    public function updateUserInfo()
    {
        $uid = $this->loginLogData['uid'];

        //系统平台
        if($this->device_system<1){
            //Log::debug('===device_info===',[$this->loginLogData['device_info']]);
            $deviceInfo = json_decode($this->loginLogData['device_info'],true);
            $isAndroid = $deviceInfo['androidId'] ??  false;
            $isIos = isset($deviceInfo['platform']) && ($deviceInfo['platform']=="ios");
            if($isAndroid!==false){
                $updateData['device_system'] = 2;
            }
            if($isIos){
                $updateData['device_system'] = 1;
            }
        }
        //
        if(!$this->code){
            $invitationCode = Str::random(2).$uid.Str::random(2);
            $updateData['promotion_code'] = $invitationCode;
            $channel_id = $this->bindChannel();
            $device_system = $updateData['device_system'] ?? $this->device_system;
            //统计安装量
            $this->saveStatisticByDay('install',$channel_id,$device_system);
        }
        if(!empty($updateData)){
            User::query()->where('id',$uid)->update($updateData);
        }
    }

    public function bindChannel(): int
    {
        //绑定渠道推广
        $lastDayDate = date('Y-m-d H:i:s',strtotime('-1 day'));
        $downloadInfo = DB::table('app_download')
            ->where('status',0)
            ->whereDate('created_at','>=', $lastDayDate)
            ->get(['id','channel_id','device_system','ip','agent_info','code','created_at'])->toArray();
        //$loginAgentInfo = $this->loginLogData['source_info'];
        $nowTime = time();
        $uid = $this->loginLogData['uid'];
        $channel_id = 0;
        foreach ($downloadInfo as $item)
        {
            $downLoadTime = strtotime($item->created_at);
            if($downLoadTime < $nowTime){
                if($this->loginLogData['ip'] == $item->ip){
                    /*$downloadAgentInfo = $item->agent_info;
                    $agentListData = explode(" ",$downloadAgentInfo);
                    $loginAgentData = explode(" ",$loginAgentInfo);
                    if($agentListData[1]===$loginAgentData[1]
                       && $agentListData[2]===$loginAgentData[2]
                       && $agentListData[3]===$loginAgentData[3]
                       && $agentListData[4]===$loginAgentData[4]
                    ){
                       $pid = DB::table('users')->where('promotion_code',$item->code)->value('id');
                       DB::table('users')->where('id',$uid)->update(['pid'=>$pid]);
                    }*/
                    $pid = DB::table('users')->where('promotion_code',$item->code)->value('id');
                    $channel_id = $item->channel_id;
                    DB::table('users')->where('id',$uid)->update(['pid'=>$pid,'channel_id'=>$item->channel_id]);
                    break;
                }
            }
        }
        return $channel_id;
    }
}
