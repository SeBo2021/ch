<?php
namespace App\Http\Controllers\Admin;

use App\Jobs\UserVideoSlice;
use App\Jobs\VideoSlice;
use App\Models\Category;
use App\Models\UserVideo;
use App\Models\Video;
use App\Services\UiService;
use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Log;

class UserVideoController extends BaseCurlController
{

    public $pageName = '视频管理';
    public $denyCommonBladePathActionName = ['create'];

    public function setModel()
    {
        return $this->model = new UserVideo();
    }

    public function indexCols()
    {
        $cols = [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'id',
                'width' => 80,
                'title' => '编号',
                'sort' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'uid',
                'width' => 150,
                'title' => '用户编号',
                'sort' => 1,
                'align' => 'center'
            ],
            /*[
                'field' => 'category_name',
                'width' => 150,
                'title' => '分类',
                'align' => 'center',
//                'edit' => 1
            ],*/
            [
                'field' => 'name',
                'minWidth' => 150,
                'title' => '片名',
                'align' => 'center',

            ],
            [
                'field' => 'duration',
                'minWidth' => 150,
                'title' => '时长',
                'align' => 'center',

            ],
            [
                'field' => 'cover_img',
                'minWidth' => 150,
                'title' => '封面图',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'url',
                'minWidth' => 150,
                'title' => '源视频',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'hls_url',
                'minWidth' => 80,
                'title' => 'hls地址',
                'align' => 'center',
            ],
            [
                'field' => 'dash_url',
                'minWidth' => 80,
                'title' => 'dash地址',
                'align' => 'center',
            ],
            /*[
                'field' => 'type',
                'minWidth' => 80,
                'title' => '视频类型',
                'align' => 'center',
            ],*/
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '是否上架',
                'align' => 'center',
            ],
            [
                'field' => 'is_recommend',
                'minWidth' => 80,
                'title' => '是否推荐',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'created_at',
                'minWidth' => 150,
                'title' => '创建时间',
                'align' => 'center'
            ],
            [
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];

        return $cols;
    }

    public function setOutputUiCreateEditForm($show = '')
    {

        $data = [
            [
                'field' => 'uid',
                'type' => 'text',
                'name' => '用户ID',
                'must' => 1,
                'attr' => 'readonly'
            ],
            [
                'field' => 'name',
                'type' => 'text',
                'name' => '片名',
                'must' => 1,
                'attr' => 'readonly'
            ],
            [
                'field' => 'author',
                'type' => 'text',
                'name' => '作者',
                'attr' => 'readonly',
                'must' => 0,
            ],
            [
                'field' => 'cover_img',
                'type' => 'img',
                'name' => '封面图片',
                'attr' => 'readonly'
//                'verify' => 'img'
            ],
            [
                'field' => 'url',
                'type' => 'userVideo',
                'name' => '视频',
            ],
            /*[
                'field' => 'title',
                'type' => 'text',
                'name' => '标题',
                'must' => 0,
                'default' => '',
            ],*/
            [
                'field' => 'status',
                'type' => 'radio',
                'name' => '是否上架',
                'verify' => '',
                'default' => 0,
                'data' => $this->uiService->trueFalseData()
            ],
            /*[
                'field' => 'is_recommend',
                'type' => 'radio',
                'name' => '推荐',
                'verify' => '',
                'default' => 0,
                'data' => $this->uiService->trueFalseData()
            ],*/

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;

    }

    //表单验证
    public function checkRule($id = '')
    {
        return [
            'name'=>'required',
        ];
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'片名',
        ];
    }

    public function setListOutputItemExtend($item)
    {
        $item->category_name = $item->category['name'] ?? '';
        $item->status = UiService::switchTpl('status', $item,'','已通过|待审核');
        $item->is_recommend = UiService::switchTpl('is_recommend', $item,'','是|否');
        $item->type = UiService::switchTpl('type', $item,'','长|短');
        return $item;
    }

    protected function afterSaveSuccessEvent($model, $id = '')
    {
        /*if( isset($_REQUEST['callback_upload']) && ($_REQUEST['callback_upload']==1)){
            //dump("slice...");
        }*/
        $job = new UserVideoSlice($model);
        try {   //先偿试队列
            //$job->delay(now()->addMinutes(1));
            $this->dispatch($job);
        }catch (\Exception $e){
            Log::error($e->getMessage());
        }
        return $model;
    }

    public function beforeSaveEvent($model, $id = '')
    {
        if(isset($_REQUEST['video_duration'])){
            $model->duration = $_REQUEST['video_duration'];
        }
        if(isset($model->url)){
            $model->dash_url = UserVideoSlice::getSliceUrl($model->url);
            $model->hls_url = UserVideoSlice::getSliceUrl($model->url,'hls');
            if(isset($model->cover_img) && !$model->cover_img){
                $model->cover_img = UserVideoSlice::getSliceUrl($model->url,'cover');
            }
        }
    }

    //弹窗大小
    public function layuiOpenWidth()
    {
        return '60%'; // TODO: Change the autogenerated stub
    }

    public function layuiOpenHeight()
    {
        return '70%'; // TODO: Change the autogenerated stub
    }

}
