<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserVideoSlice implements ShouldQueue
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

    protected $row;

    const SLICE_DIR = 'userSlice';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row)
    {
        //
        $this->row = $row;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $this->dash_slice($this->row);
        $this->hls_slice($this->row);
        //todo 更新状态值表示任务执行完成
    }

    //设置相对切片目录
    public function setSliceDir($dirName)
    {
        if(!empty($dirName)){
            $this->slice_dir = $dirName;
        }
        return $this;
    }

    //获取切片地址、或封面图
    static public function getSliceUrl($pathName,$type="dash")
    {
        $play_file_name = pathinfo($pathName,PATHINFO_FILENAME);
        $domain = env('SLICE_DOMAIN');
        $url = '';
        $sliceDir = self::SLICE_DIR;
        switch ($type)
        {
            case "dash":
                $url = $domain.'/storage/'.$sliceDir.'/'.$type.'/'.$play_file_name.'/'.$play_file_name.'.mpd';
                break;
            case "hls":
                $url = $domain.'/storage/'.$sliceDir.'/'.$type.'/'.$play_file_name.'/'.$play_file_name.'.m3u8';
                break;
            case "cover":
                $url = '/storage/'.$sliceDir.'/dash/'.$play_file_name.'/'.$play_file_name.'.jpg';
                break;
        }
        return $url;
    }

    public function dash_slice($row)
    {
        //切片转码成m4s格式文件
        $mpd_file_name = pathinfo($row->url,PATHINFO_FILENAME);
        //创建对应的切片目录
        $tmp_path = 'public/'.(self::SLICE_DIR).'/dash/'.$mpd_file_name.'/';
        $dirname = storage_path('app/').$tmp_path;
        //Log::debug('===创建用户上传文件目录===',[$mpd_file_name,$tmp_path,$dirname]);
        if(!is_dir($dirname)){
            mkdir($dirname, 0777, true);
        }

        $mpd_path = $tmp_path.$mpd_file_name.'.mpd';

        $format = new \FFMpeg\Format\Video\X264('aac', 'libx264');
        $format->setAdditionalParameters(['-vcodec', 'copy','-acodec', 'copy']); //跳过编码
        //$format = $format->setAdditionalParameters(['-hwaccels', 'cuda']);//GPU高效转码
        //增加commads的参数
//    $format = $format->setInitialParameters(['-vcodec']);
        $video = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk("local") //在storage/app的位置
        ->open($row->url)
        ->export()
        ->toDisk("local")
        ->inFormat($format);
        $video->save($mpd_path);
        //done 生成截图
        $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
        $cover_path = $tmp_path.$mpd_file_name.'.jpg';
        $frame->save($cover_path);
    }

    public function hls_slice($row)
    {
        $file_name = pathinfo($row->url,PATHINFO_FILENAME);

        //创建对应的切片目录
        $tmp_path = 'public/'.(self::SLICE_DIR).'/hls/'.$file_name.'/';
        $dirname = storage_path('app/').$tmp_path;
        if(!is_dir($dirname)){
            mkdir($dirname, 0777, true);
        }

        $m3u8_path = $tmp_path.$file_name.'.m3u8';

        $format = new \FFMpeg\Format\Video\X264('aac', 'libx264');
        //增加commads的参数,使用ffmpeg -hwaccels命令查看支持的硬件加速选项
        $format->setAdditionalParameters(['-vcodec', 'copy','-acodec', 'copy']);
        //多码率
        //$lowBitrate = (new FFMpeg\Format\Video\X264('aac', 'libx264'))->setKiloBitrate(250);
        //$midBitrate = (new FFMpeg\Format\Video\X264('aac', 'libx264'))->setKiloBitrate(500);
        //$highBitrate = (new FFMpeg\Format\Video\X264('aac', 'libx264'))->setKiloBitrate(1000);

        $video = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk("local") //在storage/app的位置
        ->open($row->url);
        $video->exportForHLS()
            ->setSegmentLength(2)//默认值是10
            ->toDisk("local")
            ->addFormat($format)
            //->addFormat($lowBitrate)
            //->addFormat($midBitrate)
            //->addFormat($highBitrate)
            ->save($m3u8_path);
    }

}
