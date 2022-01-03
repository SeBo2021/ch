<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChannelCpsByDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cps:day';

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
        //todo 每半小时执行一次
        //查`channel_cps`表
        //DB::table('channel_cps')->
        //查远程订单
        $fields = 'id,number,uid,amount,channel_id';
        $currentDate = date('Y-m-d');
        $orders = DB::connection('origin_mysql')
            ->table('orders')
            //->select(DB::raw($fields))
            ->where('status',1)
            ->whereDate('created_at',$currentDate)
            ->orderBy('id')
            ->get($fields);

        /*$n = 0;
        foreach ($orders as $order){
            $order->
        }*/
        $this->info('######渠道日统计cps执行成功######');
        return 0;
    }
}
