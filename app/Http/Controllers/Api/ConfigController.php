<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\TraitClass\AdTrait;
use App\TraitClass\PHPRedisTrait;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    use PHPRedisTrait;

    public function ack()
    {
        $appConfig = config_cache('app');
        if(!empty($appConfig)){
            $res['announcement'] = $appConfig['announcement'];
            $res['anActionType'] = $appConfig['announcement_action_type'];
            //视频ID
            $res['videoId'] = $appConfig['announcement_video_id'];
            $res['obUrl'] = $appConfig['announcement_url'];
            $res['adTime'] = (int)$appConfig['ad_time'];
            $res['version'] = $appConfig['app_version'];
            $res['kf_url'] = $appConfig['kf_url'];
            $res['send_sms_intervals'] = (int)$appConfig['send_sms_intervals'];
            //广告部分
            $ads = AdTrait::weightGet('open_screen');
            $activityAds = AdTrait::weightGet('activity');
            $res['open_screen_ads'] = $ads;
            $res['activity_ads'] = $activityAds;
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }

    /*public function upgrade(Request $request)
    {
        if(!isset($request->params)){
            return response()->json(['state'=>-1, 'msg'=>'参数错误']);
        }
        $params = Crypt::decryptString($request->params);
        $params = json_decode($params,true);
        $appid = $params['appid'];
        $version = $params['version'];
        $config = config_cache_default('config');
        if(!empty($config)){
            if($appid!=$config['app_id']){
                return response()->json(['state'=>-1, 'msg'=>'应用标识错误']);
            }
            $status = $config['app_version_name']!=$version ? 1 : 0;
            return response()->json([
                'state'=>0,
                'data'=>[
                    'status'=>$status,
                    'note'=>$config['app_update_content'],
                    'url'=>$config['app_update_url']
                ],
                'msg'=>'更新提示'
            ]);
        }
        return 0;
    }*/

}
