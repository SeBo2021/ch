<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AllocateVideoViewTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:times';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '分配观看次数';

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
        try {
            $configData = config_cache('app');
            User::query()->update(['long_vedio_times'=>$configData['free_view_long_video_times']]);
            $this->info('######观看次数分配成功######');
        }catch (\Exception $exception){
            $this->error($exception->getMessage());
        }
        return 0;
    }
}
