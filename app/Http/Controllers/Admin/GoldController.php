<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gold;
use App\Services\UiService;

class GoldController extends BaseCurlController
{
    //设置页面的名称
    public $pageName = '骚豆设置';

    //1.设置模型
    public function setModel()
    {
        return $this->model = new Gold();
    }

    public function indexCols()
    {
        $cols = [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'id',
                'minWidth' => 80,
                'title' => '编号',
                'sort' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'sort',
                'minWidth' => 80,
                'title' => '排序',
                'sort' => 1,
                'edit' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'money',
                'minWidth' => 100,
                'title' => '金额',
                'align' => 'center',
                'edit' => 1
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
                'field' => 'money',
                'type' => 'text',
                'name' => '金额',
                'must' => 1,
                'verify' => 'rq',
            ],
            [
                'field' => 'sort',
                'type' => 'number',
                'name' => '排序',
            ],
            [
                'field' => 'status',
                'type' => 'radio',
                'name' => '状态',
                'verify' => '',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ],
        ];
        $this->uiBlade['form'] = $data;
    }

    public function setListOutputItemExtend($item)
    {
        $item->status = UiService::switchTpl('status', $item,'');
        return $item;
    }
}