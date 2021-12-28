<?php

namespace App\Http\Controllers\Admin;

use App\Models\RechargeType;
use App\Services\UiService;

class RechargeTypeController extends BaseCurlController
{
    public $pageName = "充值方式";

    public function setModel()
    {
        return $this->model = new RechargeType();
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
                'field' => 'sort',
                'minWidth' => 100,
                'title' => '排序',
                'align' => 'center'
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '充值方式',
                'align' => 'center'
            ],
            [
                'field' => 'icon',
                'minWidth' => 100,
                'title' => '图标',
                'hide' => true,
                'align' => 'center'
            ],
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '状态',
                'align' => 'center',
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
                'name' => '充值方式',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'icon',
                'type' => 'number',
                'name' => '充值方式',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'sort',
                'type' => 'number',
                'name' => '排序',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'status',
                'type' => 'radio',
                'name' => '是否启用',
                'verify' => '',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ]

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;

    }

    public function setListOutputItemExtend($item)
    {
        $item->status = UiService::switchTpl('status', $item,'');
        return $item;
    }

}