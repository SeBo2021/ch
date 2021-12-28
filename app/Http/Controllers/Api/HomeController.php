<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use App\Models\Category;
use App\Models\Video;
use App\TraitClass\AdTrait;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\GoldTrait;
use App\TraitClass\PHPRedisTrait;
use App\TraitClass\VideoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    use PHPRedisTrait, GoldTrait, VideoTrait;

    public function category(Request $request)
    {
        $data = Category::query()
            ->where('parent_id',2)
            ->where('is_checked',1)
            ->orderBy('sort')
            ->get(['id','name','sort'])
            ->toArray();
        return response()->json([
            'state'=>0,
            'data'=>$data
        ]);
    }

    //轮播
    public function carousel(Request $request)
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params,[
                'cid' => 'required|integer'
            ])->validated();
            $cid = $validated['cid'];
            $data = Carousel::query()
                ->where('status', 1)
                ->where('cid', $cid)
                ->get(['id','title','img','url'])
                ->toArray();
            $domain = env('APP_URL');
            foreach ($data as &$item){
                $item['img'] = $domain . $item['img'];
            }
            return response()->json([
                'state'=>0,
                'data'=>$data
            ]);
        }
        return [];
    }

    /**
     * @throws ValidationException
     */
    public function lists(Request $request)
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params,[
                'cid' => 'required|integer',
                'page' => 'required|integer',
            ])->validate();
            $cid = $validated['cid'];
            $page = $validated['page'];
        }else{
            return [];
        }
        $redis = $this->redis();
        $sectionKey = ($this->apiRedisKey['home_lists']).$cid.'-'.$page;

        //二级分类列表
        $res = $redis->get($sectionKey);
        $perPage = 6;
        if(!$res){
            $paginator = Category::query()
                ->where('parent_id',$cid)
                ->where('is_checked',1)
                ->orderBy('sort')
                ->simplePaginate($perPage,['id','name','seo_title as title','is_rand','is_free','limit_display_num','group_type as style','group_bg_img as bg_img','local_bg_img','sort'],'',$page);
            $secondCateList = $paginator->toArray();
            $data = $secondCateList['data'];

            //加入视频列表
            foreach ($data as &$item)
            {
                //获取模块数据
                $queryBuild = DB::table('cid_vid')->join('video','cid_vid.vid','=','video.id')
                    ->where('cid_vid.cid',$item['id'])
                    ->where('video.status',1);
                if($item['is_rand']==1){
                    $queryBuild = $queryBuild->inRandomOrder();
                }else{
                    $queryBuild = $queryBuild->orderBy('video.id','desc');
                }
                $limit = $item['limit_display_num']>0 ? $item['limit_display_num'] : 8;
                $videoList = $queryBuild->limit($limit)->get($this->videoFields)->toArray();

                $videoList = $this->handleVideoItems($videoList,false,$request->user()->id);
                $item['small_video_list'] = $videoList;

                if(!empty($item['bg_img'])){
                    $item['bg_img'] = env('APP_URL').$item['bg_img'];
                }
            }
            $res['hasMorePages'] = $paginator->hasMorePages();
            $res['list'] = $data;
            //广告
            $res['list'] = AdTrait::insertAds($res['list'],'home_page',true,$page,$perPage);
            //存入redis
            $redis->set($sectionKey,json_encode($res,JSON_UNESCAPED_UNICODE));
            $redis->expire($sectionKey,$this->redisExpiredTime);
        }else{
            $res = json_decode($res,true);
        }

        return response()->json([
            'state'=>0,
            'data'=>$res
        ]);
    }

}
