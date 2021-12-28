<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Jobs\UpdateKeyWords;
use App\Models\Category;
use App\Models\CidVid;
use App\Models\KeyWords;
use App\Models\Tag;
use App\Models\Video;
use App\TraitClass\AdTrait;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\PHPRedisTrait;
use App\TraitClass\VideoTrait;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
class SearchController extends Controller
{
    use VideoTrait,PHPRedisTrait;

    /**
     * 搜索功能
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function index(Request $request)
    {
        if (isset($request->params)) {
            $perPage = 16;
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'words' => 'nullable',
                'page' => 'required|integer',
                "cid" => 'array',// 分类
                "bid" => 'array',// 版块
                "tag" => 'array', // 标签
                "type" => 'nullable', // 类型
                "sort" => 'nullable', // 排序
            ])->validate();
            $params = ApiParamsTrait::parse($request->params);
            $cats =$params['cid']??[];
            $bids = $params['bid']??[];
            // $tags = ApiParamsTrait::parse($validated['tag']??[]);
            $vIds = $this->getAllVid($cats,$bids);
            $page = $validated['page'];
            $order = $this->getOrderColumn($request->sort??-1);
            $type = $validated['type']??-1;
            $words = $validated['words']??false;
            $model = Video::search($words?:"*")->where('status', 1);
            // 分类
            if ($vIds) {
                $model->whereIn('id',$vIds);
            }
            // 类别
            if ($type != -1) {
                $model->where('restricted',$type);
            }

            // 排序
            if ($order) {
                $model->orderBy($order,'desc');
            }
            // 标签 预留
            $paginator =$model
                ->simplePaginate($perPage, 'searchPage', $page);
            $paginatorArr = $paginator->toArray()['data'];

            //$client = ClientBuilder::create()->build();
            $res['list'] = $this->handleVideoItems($paginatorArr,false,$request->user()->id);
            $res['hasMorePages'] = $paginator->hasMorePages();
            if ($words) {
                UpdateKeyWords::dispatchAfterResponse($validated['words']);
            }
            return response()->json([
                'state' => 0,
                'data' => $res
            ]);
        }
        return [];
    }

    //标签
    public function tag(Request $request)
    {
        if(isset($request->params)){
            $perPage = 16;
            $params = ApiParamsTrait::parse($request->params);
            if (isset($params['pageSize']) && ($params['pageSize'] < $perPage)) {
                $perPage = $params['pageSize'];
            }
            $page = $params['page'] ?? 1;
            $id = $params['id'] ?? 0;
            $paginator = DB::table('tid_vid')
                ->join('video','tid_vid.vid','=','video.id')
                ->where('tid_vid.tid',$id)
                ->where('video.status',1)
                ->simplePaginate($perPage,$this->videoFields,'tag',$page);
            $paginatorArr = $paginator->toArray();
            $res['list'] = $paginatorArr['data'];
            $res['list'] = $this->handleVideoItems($res['list'],false,$request->user()->id);
            $res['hasMorePages'] = $paginator->hasMorePages();
            DB::table('tag')->where('id',$id)->increment('hits');
            //$this->redis()->del($this->apiRedisKey['hot_tags']);
            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);

        }
        return [];
    }

    //更多
    public function cat(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            Validator::make($params,[
                'cid' => 'required|integer',
                'page' => 'required|integer',
            ])->validate();
            $cid = $params['cid'];
            $page = $params['page'];
            $perPage = 16;
            $paginator = DB::table('cid_vid')
                ->join('video','cid_vid.vid','=','video.id')
                ->where('cid_vid.cid',$cid)
                ->where('video.status',1)
                ->simplePaginate($perPage,$this->videoFields,'cat',$page);
            //$client = ClientBuilder::create()->build();
            $paginatorArr = $paginator->toArray();
            if(!empty($paginatorArr)){
                $res['list'] = $paginatorArr['data'];
                $res['list'] = $this->handleVideoItems($res['list'],false,$request->user()->id);
                //广告
                $res['list'] = AdTrait::insertAds($res['list'],'more_page',true, $page, $perPage);
                $res['hasMorePages'] = $paginator->hasMorePages();
                return response()->json([
                    'state'=>0,
                    'data'=>$res
                ]);
            }

        }
        return [];
    }

    //推荐
    public function recommend(Request $request): JsonResponse|array
    {
        if(isset($request->params)){
            $params = ApiParamsTrait::parse($request->params);
            Validator::make($params,[
                'vid' => 'required|integer',
            ])->validate();
            $page = $params['page'] ?? 1;
            $perPage = 8;
            $vid = $params['vid'];
            $cat = Video::query()->where('id',$vid)->value('cat');
            $cidArr = $cat ? json_decode($cat,true) : [];
            if(!empty($cidArr)){
                $paginator = DB::table('cid_vid')
                    ->join('video','cid_vid.vid','=','video.id')
                    ->whereIn('cid_vid.cid',$cidArr)
                    ->where('video.status',1)
                    ->where('video.id','!=',$vid)
                    ->distinct()
                    ->inRandomOrder()
                    ->simplePaginate($perPage,$this->videoFields,'recommend',$page);
                $paginatorArr = $paginator->toArray()['data'];
                if(!empty($paginatorArr)){
                    $res['list'] = $this->handleVideoItems($paginatorArr,false,$request->user()->id);
                    //广告
                    $res['list'] = AdTrait::insertAds($res['list'],'recommend',1);
                    $res['hasMorePages'] = $paginator->hasMorePages();
                    return response()->json([
                        'state'=>0,
                        'data'=>$res
                    ]);
                }
            }
            return response()->json([
                'state'=>0,
                'data'=>['list'=>[],'hasMorePages'=>false]
            ]);
        }
        return [];
    }

    public function hotWords(): JsonResponse
    {
        $words = KeyWords::query()
            ->orderByDesc('hits')
            ->limit(8)
            ->pluck('words');
        return response()->json([
            'state'=>0,
            'data'=>$words
        ]);
    }

    public function hotTags(): JsonResponse
    {
        $redis = $this->redis();
        $res = $redis->get($this->apiRedisKey['hot_tags']);

        if(!$res){
            $tags = Tag::query()
                ->orderBy('hits','desc')
                ->limit(15)
                ->get(['id','name']);
        }else{
            $tags = json_decode($res,true);
        }

        if(!empty($tags)){
            $redis->set($this->apiRedisKey['hot_tags'],json_encode($tags,JSON_UNESCAPED_UNICODE));
            $redis->expire($this->apiRedisKey['hot_tags'],$this->redisExpiredTime);
        }
        return response()->json([
            'state'=>0,
            'data'=>$tags
        ]);
    }

    /**
     * 得到排序标识
     * @param string $sort
     * @return string
     */
    private function getOrderColumn($sort = 'id'): string
    {
        if ($sort = $validated['page']??'id') {
            switch ($sort) {
                case '0': // hot
                    $order = 'views';
                    break;
                case '1': //new
                    $order = 'id';
                    break;
                case '2': //favor
                    $order = 'favor';
                    break;
                case '3': // like
                    $order = 'likes';
                    break;
                default:
                    $order = '';
            }
            return $order;
        }
    }

    /**
     * 得到搜索选项
     */
    public function getOption()
    {
        $data = Category::with('childs:id,name,parent_id')
            ->where('parent_id','2')
            ->select('id','name','parent_id')
            ->get();
        return response()->json([
            'state'=>0,
            'data'=>$data
        ]);
    }

    /**
     * 得到搜索选项
     */
    private function getAllVid($cid, $bid = []): array
    {
        // 数组的起始值作为缓存key
        $cacheKey = sprintf('search_vid_%s_%s',$cid[0]??0 , $bid[0]??0);
        $vids = cache()->get($cacheKey)?:[];
        if (!$vids) {
            $cids = $bid;
            if ((!$cids) && $cid) {
                $data = Category::with('childs:id,name,parent_id')
                    ->where('is_checked', 1)
                    ->whereIn('parent_id', $cid)
                    ->select('id', 'name', 'parent_id')
                    ->pluck('id')->toArray();
                $cids = array_merge($data, $cid);
            }
            $vids = $data = CidVid::query()
                ->select('vid')
                ->distinct()
                ->whereIn('cid', $cids)
                ->pluck('vid')->toArray();
            cache()->set($cacheKey,$vids,30*60);
        }
        return $vids?:($bid?[-1]:[]);
    }
}
