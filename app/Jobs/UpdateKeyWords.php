<?php


namespace App\Jobs;

use App\Models\KeyWords;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UpdateKeyWords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * 任务尝试次数
     *
     * @var int
     */
    public $tries = 3;

    //跳跃式延迟执行
    public $backoff = [60,180];

    public $words = '';
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($words)
    {
        $this->words = $words;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'http://' . env('ELASTICSEARCH_HOST') . '/_analyze?pretty';
        $response = Http::post($url, [
            'analyzer' => 'ik_smart',
            //'analyzer' => 'ik_max_word',
            'text' => $this->words,
        ])->json();
        $saveData = [];
        if (isset($response['tokens']) && !empty($response['tokens'])) {
            $response['tokens'][]['token'] = $this->words;
            foreach ($response['tokens'] as $token){
                //两个字以上才录入
                $tokenLength = mb_strwidth($token['token']);
                if($tokenLength>=4){
                    $saveData[] = [
                        'words'=>$token['token'],
                    ];
                    DB::table('key_words')->where('words',$token['token'])->increment('hits');
                }
            }
            if(!empty($saveData)){
                DB::table('key_words')->upsert($saveData,'words');
            }
        }

    }
}