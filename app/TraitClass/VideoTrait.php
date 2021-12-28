<?php

namespace App\TraitClass;

use AetherUpload\Util;
use App\Models\ViewRecord;
use Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait VideoTrait
{
    use GoldTrait;

    public object $row;

    public array $videoFields = ['video.id','name','gold','cat','sync','title','dash_url','hls_url','duration','type','restricted','cover_img','views','updated_at'];

    public string $coverImgDir = 'coverImg';

    public array $restrictedType = [
        0 => [
            'id' => 0,
            'name' => '免费'
        ],
        1 => [
            'id' => 1,
            'name' => 'VIP会员卡'
        ],
        2 => [
            'id' => 2,
            'name' => '骚豆'
        ],
    ];

    public function setRow(): object
    {
        return $this->row;
    }

    public function getRow(): object
    {
        return $this->row;
    }

    public function getMp4Path(): string
    {
        $resource = Util::getResource($this->row->url);
        return $resource->path;
    }

    //视频转码
    public function transcodeMp4($file_path,$sourceName): string
    {
        $suf = '.mp4';
        $storagePath = storage_path('app');
        $absolutePath = $storagePath.DIRECTORY_SEPARATOR.$file_path;
        $video = FFMpeg::create([
            'ffmpeg.binaries'  => env('FFMPEG_BINARIES', 'ffmpeg'),
            'ffprobe.binaries' => env('FFPROBE_BINARIES', 'ffprobe'),
            'timeout'          => 36000, // The timeout for the underlying process
            'ffmpeg.threads'   => 3,   // The number of threads that FFMpeg should use
        ])->open($absolutePath);
        $format = new X264();
        $format->setAdditionalParameters(['-vcodec', 'copy','-acodec', 'copy']); //跳过编码
        $mp4_dir = '/public/mp4';
        $mp4_full_dir = $storagePath.$mp4_dir;
        if(!is_dir($mp4_full_dir)){
            mkdir($mp4_full_dir, 0755, true);
        }
        $savePath = $mp4_full_dir.DIRECTORY_SEPARATOR.$sourceName . $suf;
        $video->save($format, $savePath);
        return $mp4_dir.DIRECTORY_SEPARATOR.$sourceName . $suf;

    }

    public function syncSlice($url, $del=false)
    {
        $dir_name = pathinfo($url,PATHINFO_FILENAME);
        $slice_dir = env('SLICE_DIR','/slice');
        $dash_directory = '/public'.$slice_dir.'/dash/'.$dir_name;
        $hls_directory = '/public'.$slice_dir.'/hls/'.$dir_name;
        $cover_img_dir = '/public'.$slice_dir.'/'.$this->coverImgDir.'/'.$dir_name;
        $dash_files = Storage::files($dash_directory);
        $hls_files = Storage::files($hls_directory);
        $cover_img = Storage::files($cover_img_dir);
        foreach ($dash_files as $file){
            $content = Storage::get($file);
            Storage::disk('sftp')->put($file,$content);
        }
        foreach ($hls_files as $file){
            $content = Storage::get($file);
            Storage::disk('sftp')->put($file,$content);
        }
        foreach ($cover_img as $img)
        {
            $content = Storage::get($img);
            Storage::disk('sftp')->put($img,$content);
        }
        if($del!==false){
            Storage::deleteDirectory($dash_directory);
            Storage::deleteDirectory($hls_directory);
        }
    }

    public function syncUpload($img)
    {
        $abPath = public_path().$img;
        if(file_exists($abPath)){
            $content = file_get_contents($abPath);
            Storage::disk('sftp')->put($img,$content);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function generatePreview($preview)
    {
        $dir_name = pathinfo($preview->url,PATHINFO_FILENAME);
        $slice_dir = env('SLICE_DIR','/slice');
        $dash_directory = '/public'.$slice_dir.'/dash/'.$dir_name;
        $hls_directory = '/public'.$slice_dir.'/hls/'.$dir_name;
        //dash预览
        $dash_play_file = $dash_directory .'/'. $dir_name.'.mpd';
        $exists_dash = Storage::disk('sftp')->exists($dash_play_file);
        if($exists_dash){
            $content_dash = Storage::disk('sftp')->get($dash_play_file);
            if($content_dash){
                $xml_object = simplexml_load_string($content_dash);
                $xml_object['mediaPresentationDuration'] = 'PT0M30S';
                $xml_content = $xml_object->asXML();
                $dash_file = $dash_directory.'/preview.mpd';
                Storage::disk('sftp')->put($dash_file,$xml_content);
            }
        }

        //hls预览
        $hls_play_file = $hls_directory . '/' . $dir_name.'.m3u8';
        $hls_handle_play_file = Storage::disk('sftp')->exists($hls_play_file);
        if($hls_handle_play_file){
            $lines = explode("\n",Storage::disk('sftp')->get($hls_play_file));
            $initHlsFile = '';
            foreach ($lines as $line) {
                if(str_contains($line, '.m3u8')){
                    $initHlsFile = $hls_directory . '/' . $line;
                }
            }
            $hls_handle_init_file = Storage::disk('sftp')->exists($initHlsFile);
            if($hls_handle_init_file){
                $hls_file = $hls_directory . '/preview.m3u8';
                $trimmed = explode("\n",Storage::disk('sftp')->get($initHlsFile));
                $second = 0;
                $breakLineNum = -1;
                $hlsContentLines = '';
                foreach ($trimmed as $key => $val) {
                    if($breakLineNum>0 && ($key==$breakLineNum+2)){
                        $hlsContentLines .= "#EXT-X-ENDLIST\n";
                        break;
                    }
                    $hlsContentLines .= $val."\n";
                    if(str_contains($val, '#EXTINF')){
                        $block_s = rtrim(explode(':',$val)[1],',');
                        $block_s += 0;
                        if($second < 31){
                            if($block_s<31){
                                $second = round($second + $block_s);
                            }else{
                                $second = $block_s;
                                $breakLineNum = $key;
                            }
                        }else{
                            $breakLineNum = $key;
                        }
                    }
                }
                if(!empty($hlsContentLines)){
                    Storage::disk('sftp')->put($hls_file, $hlsContentLines);
                }
            }
        }

    }

    public function syncMiddleSectionTable()
    {
        DB::beginTransaction();
        try {
            $Video = DB::table('video')->get(['id','cat']);
            DB::table('cid_vid')->where('cid','>',0)->delete();
            $insertArr = [];
            foreach ($Video as $item)
            {
                $catArr = $item->cat ? @json_decode($item->cat) : [];
                if(!empty($catArr)){
                    foreach ($catArr as $cid){
                        if($cid > 0){
                            $insertArr[$cid.'-'.$item->id] = ['cid'=>$cid, 'vid'=>$item->id];
                        }
                    }
                }
            }
            //dump('版块中间表执行:'.count($insertArr).'条');
            foreach ($insertArr as $insertValue){
                DB::table('cid_vid')->insertOrIgnore($insertValue);
            }
            DB::commit();
        }catch (Exception $e){
            Log::error('syncMiddleSectionTable==='.$e->getMessage());
            DB::rollBack();
        }

    }

    public function syncMiddleTagTable()
    {
        DB::beginTransaction();
        try {
            $this->syncMiddleTagProcess(1, 100);
        } catch (Exception $e) {
            Log::error('syncMiddleTagTable===' . $e->getMessage());
            DB::rollBack();
        }
    }

    private function syncMiddleTagProcess($page, $limit)
    {
        $Video = DB::table('video')->offset(($page - 1) * $limit)->limit($limit)->get(['id', 'tag']);
        DB::table('tid_vid')->where('tid', '>', 0)->delete();
        $insertArr = [];
        foreach ($Video as $item) {
            $catArr = $item->tag ? @json_decode($item->tag) : [];
            if (!empty($catArr)) {
                foreach ($catArr as $tid) {
                    $insertArr[$tid . '-' . $item->id] = ['tid' => $tid, 'vid' => $item->id];
                }
            }
        }
        $page++;
        //dump('标签中间表执行:'.count($insertArr).'条');
        if (!empty($insertArr)) {
            DB::table('tid_vid')->insertOrIgnore($insertArr);
        }
        if (count($insertArr) == $limit) {
            $this->syncMiddleTagProcess($page, $limit);
        }
    }

    public static function getDomain($sync)
    {
        return $sync==1 ? env('RESOURCE_DOMAIN') : env('SLICE_DOMAIN');
    }

    //获取切片链接地址、封面图
    public static function get_slice_url($pathName,$type="dash",$sync=null): string
    {
        $play_file_name = pathinfo($pathName,PATHINFO_FILENAME);
        $sliceDir = env('SLICE_DIR','/slice');
        $url = match ($type) {
            "dash" => '/storage' . $sliceDir . '/' . $type . '/' . $play_file_name . '/' . $play_file_name . '.mpd',
            "hls" => '/storage' . $sliceDir . '/' . $type . '/' . $play_file_name . '/' . $play_file_name . '.m3u8',
            "cover" => '/storage' . $sliceDir . '/coverImg/' . $play_file_name . '/' . $play_file_name . '.jpg',
        };
        if($sync!==null){
            $url = self::getDomain($sync).$url;
        }
        return $url;
    }

    public function getSearchCheckboxResult($items,$inputData,$field)
    {
        if(!empty($inputData)){
            $is_none = end($inputData)==0;
            $result = [];
            foreach ($items as $item){
                if(!$item->$field){
                    $item->$field = '{}';
                }
                if(!$is_none){
                    $intersection = array_intersect(json_decode($item->$field,true),$inputData);
                    if(!empty($intersection)){
                        $result[] = $item;
                    }
                }else{
                    if($item->$field=='[]'){
                        $result[] = $item;
                    }
                }
            }
            return $result;
        }
        return $items;
    }

    public function formatSeconds($seconds): string
    {
        $hour = floor($seconds/3600);
        $minute = floor(($seconds-3600 * $hour)/60);
        $seconds = floor((($seconds-3600 * $hour) - 60 * $minute) % 60);
        if($hour<10){
            $hour = "0".$hour;
        }
        if($minute<10){
            $minute = "0".$minute;
        }
        if($seconds<10){
            $seconds = "0".$seconds;
        }
        return $hour.':'.$minute.':'.$seconds;
    }

    public function transferSeconds($format)
    {
        $durationArr = explode(':', $format);
        $length = count($durationArr);
        $h=0;
        $i=0;
        $s=0;
        if($length == 3){
            $h = $durationArr[0] ?? 0;
            $i = $durationArr[1] ?? 0;
            $s = $durationArr[2] ?? 0;
        }elseif ($length == 2){
            $h = 0;
            $i = $durationArr[0] ?? 0;
            $s = $durationArr[1] ?? 0;
        }elseif ($length == 1){
            $h = 0;
            $i = 0;
            $s = $format;
        }
        return $h*3600 + $i*60 + $s;
    }

    public function handleVideoItems($lists,$display_url=false,$uid = 0)
    {
        foreach ($lists as &$list){
            $list = (array)$list;
            $domainSync = VideoTrait::getDomain($list['sync']);
            $list['cover_img'] = $domainSync.$list['cover_img'];
            $list['gold'] = $list['gold']/$this->goldUnit;
            $list['views'] = $list['views']>0 ? $this->generateRandViews($list['views']) : $this->generateRandViews(rand(5,9));
            $list['hls_url'] = $domainSync . $list['hls_url'];
            $list['preview_hls_url'] = $this->getPreviewPlayUrl($list['hls_url']);
            $list['dash_url'] = $domainSync . $list['dash_url'];
            $list['preview_dash_url'] = $this->getPreviewPlayUrl($list['dash_url'],'dash');
            if(!$display_url){
                unset($list['hls_url']);
                unset($list['dash_url']);
            }

            //是否点赞
            $viewRecord = $this->isLoveOrCollect($uid,$list['id']);
            $list['is_love'] = $viewRecord['is_love'] ?? 0;
            //是否收藏
            $list['is_collect'] = $viewRecord['is_collect'] ?? 0;
        }
        return $lists;
    }

    public function generateRandViews($views): string
    {
        return ($views*10) * round(rand(1,9)/10,1).'万';
    }

    public function getPreviewPlayUrl($url,$type='hls'): array|string
    {
        $name = basename($url);
        $typeArr = [
            'hls' => '.m3u8',
            'dash' => '.mpd'
        ];
        return str_replace($name,'preview' . ($typeArr[$type]),$url);
    }

    /**
     * 判断是否收藏或喜欢
     * @param int $uid
     * @param $vid
     * @return int[]
     */
    public function isLoveOrCollect($uid = 0,$vid = 0): array
    {
        $one = [
            'is_love'=>0,
            'is_collect'=>0,
        ];
        if (!$uid) {
            return $one;
        }
        $viewRecord = ViewRecord::query()->where('uid', $uid)->where('vid', $vid)->first(['id', 'is_love', 'is_collect']);
        //是否点赞
        $one['is_love'] = $viewRecord['is_love'] ?? 0;
        //是否收藏
        $one['is_collect'] = $viewRecord['is_collect'] ?? 0;
        return $one;
    }

}