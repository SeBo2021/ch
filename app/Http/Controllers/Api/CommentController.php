<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\TraitClass\ApiParamsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function submit(Request $request)
    {
        if(isset($request->params)) {
            $params = ApiParamsTrait::parse($request->params);
            Validator::make($params, [
                'vid' => 'required|integer',
                'content' => 'required',
            ])->validate();
            $vid = $params['vid'];
            $content = $params['content'];
            $insertData = [
                'vid' => $vid,
                'uid' => $request->user()->id,
                'content' => $content,
                'reply_at' => date('Y-m-d H:i:s'),
            ];
            DB::beginTransaction();
            try {   //先偿试队列
                $commentId = DB::table('comments')->insertGetId($insertData);
                Video::where('id',$vid)->increment('comments');
                DB::commit();
                if($commentId >0){
                    return response()->json([
                        'state'=>0,
                        'msg'=>'评论成功'
                    ]);
                }
            }catch (\Exception $e){
                DB::rollBack();
                Log::error('createUser===' . $e->getMessage());
            }
            return response()->json([
                'state'=>-1,
                'msg'=>'评论失败'
            ]);

        }
        return [];
    }

    public function reply(Request $request)
    {
        if(isset($request->params)) {
            $params = ApiParamsTrait::parse($request->params);
            $validated = Validator::make($params, [
                'comment_id' => 'required|integer|min:1',
                'vid' => 'required|integer|min:1',
                'content' => 'required',
            ])->validated();

            $replied_uid = DB::table('comments')->where('id',$validated['comment_id'])->value('uid');
            $comment = DB::table('comments')->find($validated['comment_id'],['reply_cid']);
            if($comment->reply_cid>0){
                $validated['comment_id'] = DB::table('comments')->where('id',$validated['comment_id'])->value('reply_cid');
            }
            $insertData = [
                'reply_cid' => $validated['comment_id'],
                'vid' => $validated['vid'],
                'uid' => $request->user()->id,
                'replied_uid' => $replied_uid,
                'content' => $validated['content'],
                'reply_at' => date('Y-m-d H:i:s'),
            ];
            DB::beginTransaction();
            DB::table('comments')->insert($insertData);
            DB::table('comments')->where('id',$validated['comment_id'])->increment('replies');
            DB::commit();
            return response()->json([
                'state'=>0,
                'msg'=>'回复成功'
            ]);
        }
        return [];
    }

    public function lists(Request $request)
    {
        if(isset($request->params)) {
            $params = ApiParamsTrait::parse($request->params);
            Validator::make($params, [
                'vid' => 'required|integer',
            ])->validate();
            $reply_cid = $params['comment_id'] ?? 0;
            $vid = $params['vid'];
            $page = $params['page'] ?? 1;
            $perPage = 16;
            $fields = ['comments.id','vid','uid','reply_cid','replied_uid','content','replies','reply_at','users.avatar','users.nickname'];
            $queryBuild = DB::table('comments')
                ->join('users','comments.uid','=','users.id')
                ->where('comments.vid',$vid);
            $queryBuild = $queryBuild->where('reply_cid',$reply_cid);
            $paginator = $queryBuild->orderBy('id')->simplePaginate($perPage,$fields,'commentLists',$page);
            $items = $paginator->items();
            $res['list'] = $items;
            $res['hasMorePages'] = $paginator->hasMorePages();

            $replied_uid = [];
            foreach ($res['list'] as &$item){
                if($item->replied_uid>0){
                    $replied_uid[] = $item->replied_uid;
                }
            }
            $repliedUser = DB::table('users')->whereIn('id',$replied_uid)->pluck('nickname','id')->all();
            foreach ($res['list'] as &$item){
                if($item->replied_uid>0){
                    $item->replied_nickname = $repliedUser[$item->replied_uid];
                }
            }

            return response()->json([
                'state'=>0,
                'data'=>$res
            ]);
        }
        return [];
    }
}