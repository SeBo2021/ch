<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Jobs\UserVideoSlice;
use App\Models\UserVideo;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    static public function getUrl($relativePath)
    {
        return asset( '/storage'.ltrim($relativePath,'public'));
    }

    public function uploadVideo(Request $request)
    {
        $relativePath = $request->file("file")->store('public/userVideo');
        $user = $request->user();
        //保存
        if($relativePath && isset($request->params)){
            # 对请求参数进行解密处理
            $params = Crypt::decryptString($request->params);
            $params = json_decode($params,true);
            $video = new UserVideo();
            $video->name = $params['title'];
            $video->description = $params['description'];
            $video->url = $relativePath;
            $video->type = 2;
            $video->source = '用户上传';
            $video->uid = $user->id;
            $video->author = $user->nickname;
            $video->save();
            /*if($video->save()){
                try {
                    UserVideoSlice::dispatchAfterResponse($video);
                }catch (\Exception $e){
                    Log::error($e->getMessage());
                }
            }*/
        }
        $data = array(
            'state' => 0,
            'msg'  => '上传成功',
            'data' => array(
                'src'   => self::getUrl($relativePath),
            ),
        );
        return response()->json($data);
    }

    public function uploadImg(Request $request)
    {
        $relativePath = $request->file("file")->store('public/userImg');
        $data = array(
            'code' => 0,
            'msg'  => '上传成功',
            'data' => array(
                'src'   => self::getUrl($relativePath)
            ),
//            'params' => $params
        );
        return response()->json($data);
    }

}
