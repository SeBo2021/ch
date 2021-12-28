<?php

namespace App\Console\Commands;

use App\TraitClass\PHPRedisTrait;
use App\TraitClass\VideoTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMiddleTable extends Command
{
    use PHPRedisTrait, VideoTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:middleTable';

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
        //同步版块中间表
        $this->syncMiddleSectionTable();
        //同步标签中间表
        $this->syncMiddleTagTable();
        //清除缓存
        $this->redisBatchDel($this->redis()->keys($this->apiRedisKey['home_lists'] . '*'));
        $this->info('######视频中间表同步成功######');
        return 0;
    }
}
