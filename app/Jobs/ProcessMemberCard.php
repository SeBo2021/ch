<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessMemberCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uid;

    public $card_id;

    public $expired_time;

    /**
     * Create a new job instance.
     *
     * @param $uid
     * @param $card_id
     * @param int $expired_time
     */
    public function __construct($uid,$card_id,$expired_time = 0)
    {
        $this->uid = $uid;
        $this->card_id = $card_id;
        $this->expired_time = $expired_time;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = DB::table('users')->where('status',1)->find($this->uid,['id','member_card_type','vip_expired','vip_start_last']);
        if(!empty($user)){
            $cards = explode(',',$user->member_card_type);
            $cards[] = $this->card_id;
            // 可重复购买
            /*foreach ($cards as $key => $card){
                if($card == $this->card_id){
                    unset($cards[$key]);
                    break;
                }
            }*/

            $cards = !empty($cards) ? implode(',',$cards) : '';
            $now = time();
            // 用户余下vip时间
            $calc = ($user->vip_expired?:0) - ($now-($user->vip_start_last?:$now));
            if ($calc >= 0) {
                $vipExpired = $calc+$this->expired_time;
            } else {
                $vipExpired = $this->expired_time;
            }
            DB::table('users')->where('id',$this->uid)->update([
                'member_card_type'=>$cards,
                 // 'vip'=>1,
                'vip_start_last' => $now, // 最后vip开通时间
                'vip_expired' => $vipExpired
            ]);
        }
    }
}
