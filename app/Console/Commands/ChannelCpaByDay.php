<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChannelCpaByDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpa:day';

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
        $channels = DB::connection('origin_mysql')->table('channels')->where('status',1)->where('type',0)
            ->get(['id','pid','name','promotion_code','number','share_ratio']);
        $currentDate = date('Y-m-d');
        $currentTime = strtotime($currentDate);
        foreach ($channels as $channel) {
            $exists = DB::connection('origin_mysql')->table('statistic_day_deduction')->where('channel_id',$channel->id)->where('at_time',$currentTime)->exists();
            if(!$exists){
                $insertData = [
                    'channel_id' => $channel->id,
                    'pid' => $channel->pid,
                    'at_time' => $currentTime,
                ];
                DB::connection('origin_mysql')->table('statistic_day_deduction')->insert($insertData);
            }
        }
        $this->info('######渠道日统计cpa执行成功######');
        return 0;
    }
}
