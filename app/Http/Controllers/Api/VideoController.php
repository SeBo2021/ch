<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Jobs\ProcessViewVideo;
use App\Models\Domain;
use App\Models\GoldLog;
use App\Models\User;
use App\Models\Video;
use App\Models\ViewRecord;
use App\TraitClass\ApiParamsTrait;
use App\TraitClass\PHPRedisTrait;
use App\TraitClass\VideoTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    use VideoTrait;
    use PHPRedisTrait;

    //播放
    public function actionView(Request $request)
    {
        $user = $request->user();
        $viewLongVideoTimes = $user->long_vedio_times; //观看次数
        //todo 是否会员的逻辑处理，暂时每天免费三次
        if (!($request->params ?? false)) {
            return response()->json([
                'state' => -1,
                'msg' => "参数错误",
            ]);
        }
        // 业务逻辑
        try {
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'id' => 'required|integer||min:1',
                'use_gold' => [
                    'nullable',
                    'string',
                    Rule::in(['1', '0']),
                ],
            ])->validated();
            $id = $validated['id'];
            $useGold = $validated['use_gold'] ?? "1";
            $videoField = ['id', 'name', 'cid', 'cat', 'restricted', 'sync', 'title', 'url', 'gold', 'duration', 'hls_url', 'dash_url', 'type', 'cover_img', 'views', 'likes', 'comments','updated_at'];
            $one = Video::query()->find($id, $videoField)?->toArray();
            if (!empty($one)) {
                $one = $this->handleVideoItems([$one], true)[0];
                $one['limit'] = 0;
                // 任何类型都有 是否点赞 is_collect 并增加观看记录
                ProcessViewVideo::dispatchAfterResponse($user, $one);
                //是否点赞
                $viewRecord = $this->isLoveOrCollect($user->id,$id);
                $one['is_love'] = $viewRecord['is_love'] ?? 0;
                //是否收藏
                $one['is_collect'] = $viewRecord['is_collect'] ?? 0;

                if ($one['restricted'] != 0) {
                    //是否有观看次数
                    if ($viewLongVideoTimes <= 0) {
                        $one['restricted'] += 0;
                        /*if ($user->phone_number > 0) {*/
                            // unset($one['preview_hls_url'], $one['preview_dash_url']);
                            $one = $this->vipOrGold($one, $user);
                            if ($useGold && $one['limit'] == 2) {
                                // 如果金币则尝试购买
                                $buy = $this->useGold($one, $user);
                                $buy && ($one['limit'] = 0);
                            }
                            return response()->json([
                                'state' => 0,
                                'data' => $one
                            ]);
                        /*} else {
                            // unset($one['hls_url']);
                            // unset($one['dash_url']);
                            return response()->json([
                                'state' => -1,
                                'data' => $one,
                                'msg' => "绑定手机可全站免费观看",
                            ]);
                        }*/


                    }
                }
            }
            return response()->json([
                'state' => 0,
                'data' => $one
            ]);
        } catch (Exception $exception) {
            $msg = $exception->getMessage();
            Log::error("actionView", [$msg]);
        }
        return 0;
    }

    //点赞
    public function actionLike(Request $request)
    {
        if (isset($request->params)) {
            $user = $request->user();
            $params = ApiParamsTrait::parse($request->params);
            $rules = [
                'id' => 'required|integer',
                'like' => 'required|integer',
            ];
            Validator::make($params, $rules)->validate();
            $id = $params['id'];
            $is_love = $params['like'];
            try {
                if ($is_love) {
                    Video::query()->where('id', $id)->increment('likes');
                } else {
                    Video::query()->where('id', $id)->decrement('likes');
                }
                $attributes = ['uid' => $user->id, 'vid' => $id];
                $values = ['is_love' => $is_love];
                ViewRecord::query()->updateOrInsert($attributes, $values);
                return response()->json([
                    'state' => 0,
                    'data' => [],
                ]);
            } catch (Exception $exception) {
                $msg = $exception->getMessage();
                Log::error("actionLike", [$msg]);
            }
        } else {
            return response()->json([
                'state' => -1,
                'msg' => "参数错误",
            ]);
        }
        return 0;
    }

    public function actionShare(Request $request)
    {
        $user = $request->user();
        $code = $user->promotion_code ?? null;
        if (!empty($code)) {
            $domainArr = Domain::query()
                ->where('status', 1)
                ->where('type', '<', 2)
                ->get(['id', 'name'])->toArray();
            $randKey = array_rand($domainArr);
            $domain = $domainArr[$randKey]['name'];
            $promotion_url = $domain . '?code=' . $code;
            //奖励规则
            $appConfig = config_cache('app');
            return response()->json([
                'state' => 0,
                'data' => [
                    'invite_code' => $code,
                    'reward_rules' => $appConfig['reward_rules'] ?? '',
                    'promotion_url' => $promotion_url
                ],
            ]);
        }
        return [];
    }

    //
    public function actionCollect(Request $request)
    {
        if (isset($request->params)) {
            $user = $request->user();
            $params = ApiParamsTrait::parse($request->params);
            $rules = [
                'id' => 'required|integer',
                'collect' => 'required|integer',
            ];
            Validator::make($params, $rules)->validate();
            $id = $params['id'];
            $is_collect = $params['collect'];
            $card = explode(',',($user->member_card_type??[]));
            if (!array_intersect([3,4,5,6,7,8],$card)){
                return response()->json([
                    'state' => -2,
                    'msg' => "权限不足",
                ]);
            }
            try {
                Video::query()->where('id', $id)->increment('likes');
                $attributes = ['uid' => $user->id, 'vid' => $id];
                $values = ['is_collect' => $is_collect];
                ViewRecord::query()->updateOrInsert($attributes, $values);
                return response()->json([
                    'state' => 0,
                    'data' => [],
                ]);
            } catch (Exception $exception) {
                $msg = $exception->getMessage();
                Log::error("actionLike", [$msg]);
            }
        } else {
            return response()->json([
                'state' => -1,
                'msg' => "参数错误",
            ]);
        }
        return [];
    }

    /**
     * 金豆判断
     * @param $one
     * @param $user
     * @return mixed
     */
    public function vipOrGold($one, $user): mixed
    {
        switch ($one['restricted']) {
            case 1:
                if ((!$user->member_card_type) && (time() - $user->vip_expired > $user->vip_start_last)) {
                    $one['limit'] = 1;
                }
                break;
            case 2:
                $redisHashKey = $this->apiRedisKey['user_gold_video'] . $user->id;
                $buy = $this->redis()->sIsMember($redisHashKey, $one['id']);
                if (!$buy) {
                    $one['limit'] = 2;
                }

        }
        return $one;
    }

    /**
     * 花费金豆
     * @param $one
     * @param $user
     * @return mixed
     */
    public function useGold($one, $user): mixed
    {
        // 扣除金币
        $redisHashKey = $this->apiRedisKey['user_gold_video'] . $user->id;
        $now = date('Y-m-d H:i:s', time());
        $newGold = $user->gold - $one['gold'];
        $model = User::query();
        $userEffect = $model->where('id', '=', $user->id)
            ->where('gold', '>=', $one['gold'])
            ->update(
                ['gold' => $newGold]
            );
        if (!$userEffect) {
            return false;
        }
        $logEffect = GoldLog::query()->create([
            'uid' => $user->id,
            'goods_id' => $one['id'],
            'cash' => $one['gold'],
            'goods_info' => json_encode($one),
            'before_cash' => $user->gold,
            'use_type' => 1,
            'device_system' => $user->device_system,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        if (!$logEffect) {
            return false;
        }
        $this->redis()->sAdd($redisHashKey, $one['id']);
        return true;
    }
}