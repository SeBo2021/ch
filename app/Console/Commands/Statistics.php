<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Statistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:keep';

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
        $startDate = date('Y-m-d',strtotime('-32 day'));
        $endDate = date('Y-m-d',strtotime('-1 day'));
        $startTime =  strtotime($startDate);
        $endTime =  strtotime($endDate);
        $usersDay = DB::table('users_day')
            ->whereBetween('at_time',[$startTime, $endTime])
            ->orderBy('at_time')
            ->get(['id','uid','channel_id','device_system','at_time']);

        $yesterdayStatistics = [];
        $firstDayUsers = [];

        $dayUsers = [];
        $weekUsers = [];
        $monthUsers = [];

        $firstDayTime = 0;

        foreach ($usersDay as $k => $item){
            $key = $item->channel_id. '|' . $item->device_system;
            //统计日前一日
            if($item->at_time==$endTime){
                $yesterdayStatistics[$key][$endTime] = 1;
            }
            if($k==0){
                $firstDayTime = $item->at_time;
            }
            //第1日
            if($item->at_time==$firstDayTime){
                $firstDayUsers[$key][$item->uid] = $item->uid;
            }
            //第1日后1日
            if($item->at_time == $firstDayTime+24*3600){
                $dayUsers[$key][$item->uid] = $item->uid;
            }
            //第7日后1日
            if($item->at_time == $firstDayTime+24*3600*7){
                $weekUsers[$key][$item->uid] = $item->uid;
            }
            //第30日后1日
            if($item->at_time == $firstDayTime+24*3600*30){
                $monthUsers[$key][$item->uid] = $item->uid;
            }

        }

        foreach ($yesterdayStatistics as $unionKey => $value){
            $keyArr = explode('|',$unionKey);
            //留存数
            $keep_day_users = isset($dayUsers[$unionKey]) && isset($firstDayUsers[$unionKey]) ? count(array_intersect($dayUsers[$unionKey],$firstDayUsers[$unionKey])) : 0;
            $keep_week_users = isset($weekUsers[$unionKey]) && isset($firstDayUsers[$unionKey]) ? count(array_intersect($weekUsers[$unionKey],$firstDayUsers[$unionKey])) : 0;
            $keep_month_users = isset($monthUsers[$unionKey]) && isset($firstDayUsers[$unionKey]) ? count(array_intersect($monthUsers[$unionKey],$firstDayUsers[$unionKey])) : 0;
            $first_day_users = isset($firstDayUsers[$unionKey]) ? count($firstDayUsers[$unionKey]) : 0;
            DB::table('statistic_day')
                ->where('channel_id',$keyArr[0])
                ->where('device_system',$keyArr[1])
                ->where('at_time',$endTime)
                ->update([
                    'first_day_users' => $first_day_users,
                    'keep_day_users' => $keep_day_users,
                    'keep_week_users' => $keep_week_users,
                    'keep_month_users' => $keep_month_users,
                    'keep_day_rate' => $first_day_users>0 ? round($keep_day_users/$first_day_users,2)*100 : 0,
                    'keep_week_rate' => $first_day_users>0 ? round($keep_week_users/$first_day_users,2)*100 : 0,
                    'keep_month_rate' => $first_day_users>0 ? round($keep_month_users/$first_day_users,2)*100 : 0,
            ]);
        }

        $this->info('######统计用户留存执行成功######');
        return 0;
    }
}
