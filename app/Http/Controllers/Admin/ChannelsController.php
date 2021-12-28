<?php

namespace App\Http\Controllers\Admin;

use App\Models\Channel;
use App\Services\UiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChannelsController extends BaseCurlController
{
    public $pageName = '渠道';

    public $channelType = [
        0 => [
            'id' => 0,
            'name' => 'CPA'
        ],
        1 => [
            'id' => 1,
            'name' => '包月'
        ],
        2 => [
            'id' => 2,
            'name' => 'CPS'
        ],
    ];

    public function setModel()
    {
        return $this->model = new Channel();
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
                'field' => 'type',
                'minWidth' => 100,
                'title' => '渠道类型',
                'align' => 'center'
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
                'align' => 'center'
            ],
            [
                'field' => 'promotion_code',
                'minWidth' => 100,
                'title' => '推广码',
                //'edit' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'deduction',
                'minWidth' => 100,
                'title' => '扣量(点)',
                //'edit' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'number',
                'minWidth' => 80,
                'title' => '渠道码',
                'hide' => true,
                'align' => 'center',
            ],
            [
                'field' => 'url',
                'minWidth' => 80,
                'title' => '渠道推广链接',
                'align' => 'center',
            ],
            [
                'field' => 'statistic_url',
                'minWidth' => 80,
                'title' => '统计链接地址',
                'align' => 'center',
            ],
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '状态',
                'align' => 'center',
            ],
            [
                'field' => 'created_at',
                'minWidth' => 150,
                'title' => '创建时间',
                'align' => 'center'
            ],
            [
                'field' => 'updated_at',
                'minWidth' => 150,
                'title' => '更新时间',
                'hide' => true,
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
                'field' => 'name',
                'type' => 'text',
                'name' => '渠道名称',
                'must' => 1,
                'default' => '',
            ],
            [
                'field' => 'promotion_code',
                'type' => 'text',
                'name' => '推广码',
                'must' => 1,
            ],
            [
                'field' => 'deduction',
                'type' => 'number',
                'name' => '扣量(点)',
                'value' => ($show && ($show->deduction>0)) ? $show->deduction/100 : 50,
                'must' => 1,
                'default' => '50',
            ],
            [
                'field' => 'type',
                'type' => 'radio',
                'name' => '类型',
                'must' => 0,
                'default' => 0,
                'verify' => 'rq',
                'data' => $this->channelType
            ],
        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }

    public function beforeSaveEvent($model, $id = '')
    {
        $model->status = 1;
        $model->deduction *= 100;
        if($id>0 && $model->deduction>0){
            $originalDeduction = $model->getOriginal()['deduction'];
            if($originalDeduction != $model->deduction){
                //dd('修改扣量');
                $this->writeChannelDeduction($id,$model->deduction);
            }
        }
    }

    public function writeChannelDeduction($id, $deduction=5000, $date=null)
    {
        $insertData = [
            'channel_id' => $id,
            'deduction' => $deduction,
            'created_at' =>$date ?? date('Y-m-d H:i:s'),
        ];
        DB::table('statistic_channel_deduction')->insert($insertData);
    }

    public function afterSaveSuccessEvent($model, $id = '')
    {
        if($id == ''){
            $model->number = 'S'.Str::random(6) . $model->id;
            $one = DB::table('domain')->where('status',1)->inRandomOrder()->first();
            switch ($model->type){
                case 0:
                    $model->url = $one->name . '?'.http_build_query(['channel_id' => $model->promotion_code]);
                    break;
                case 1:
                    $model->url = $one->name . '/downloadFast?'.http_build_query(['channel_id' => $model->promotion_code]);
                    break;
            }
            $model->statistic_url = env('RESOURCE_DOMAIN') . '/channel/index.html?' . http_build_query(['code' => $model->number]);
            //https://sao.yinlian66.com/channel/index.html?code=1
            $model->save();
            //
            $this->writeChannelDeduction($model->id,$model->deduction,$model->updated_at);
        }
        return $model;
    }

    public function setListOutputItemExtend($item)
    {
        $item->deduction /= 100;
        $item->status = UiService::switchTpl('status', $item,'');
        $item->type = $this->channelType[$item->type]['name'];
        return $item;
    }

    //表单验证
    public function checkRule($id = '')
    {
        $data = [
            'name'=>'required|unique:channels,name',
            'promotion_code'=>'required|unique:channels,promotion_code',
        ];
        //$id值存在表示编辑的验证
        if ($id) {
            $data['name'] = 'required|unique:channels,name,' . $id;
            $data['promotion_code'] = 'required|unique:channels,promotion_code,' . $id;
        }
        return $data;
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'渠道名称',
            'promotion_code'=>'推广码',
        ];
    }
}