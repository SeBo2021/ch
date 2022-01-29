<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChannelStatisticByDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $channels = DB::connection('origin_mysql')->table('channels')->where('status',1)->where('type',2)
            ->get(['id','pid','name','promotion_code','number','share_ratio']);
        $currentDate = date('Y-m-d');
        foreach ($channels as $channel) {
            $exists = DB::table('channel_day_statistics')->where('channel_id',$channel->id)->where('date_at',$currentDate)->exists();
            if(!$exists){
                $insertData = [
                    'channel_name' => $channel->name,
                    'channel_id' => $channel->id,
                    'channel_pid' => $channel->pid,
                    'channel_promotion_code' => $channel->promotion_code,
                    'channel_code' => $channel->number,
                    'share_ratio' => $channel->share_ratio,
                    'total_recharge_amount' => 0,
                    'total_amount' => 0,
                    'total_orders' => 0,
                    'order_index' => 0,
                    'usage_index' => 0,
                    'share_amount' => 0,
                    'date_at' => $currentDate,
                ];
                DB::table('channel_cps')->insert($insertData);
            }
        }
        $this->info('######渠道日统计初始化数据执行成功######');
        return 0;
    }
}
