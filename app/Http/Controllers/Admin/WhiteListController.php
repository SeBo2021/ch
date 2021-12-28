<?php

namespace App\Http\Controllers\Admin;

use App\Models\WhiteList;
use App\Services\UiService;

class WhiteListController extends BaseCurlController
{
    //设置页面的名称
    public $pageName = '白名单';

    //1-后台登录
    public $type = [
        0 => [
            'id' => 0,
            'name' => '无'
        ],
        1 => [
            'id' => 1,
            'name' => '后台登录'
        ],
    ];

    //1.设置模型
    public function setModel()
    {
        return $this->model = new WhiteList();
    }

    public function indexCols()
    {
        $data = [
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
                'field' => 'ip',
                'minWidth' => 100,
                'title' => 'IP地址',
                'align' => 'center',
            ],
            [
                'field' => 'type',
                'minWidth' => 80,
                'title' => '类型',
                'align' => 'center'
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
                'align' => 'center'
            ],
            [
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];
        //要返回给数组
        return $data;
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'ip',
                'type' => 'text',
                'name' => 'IP地址',
                'must' => 1,
                'default' => '',
            ],
            [
                'field' => 'type',
                'type' => 'radio',
                'name' => 'IP地址',
                'must' => 1,
                'default' => 1,
                'data' => $this->type
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
        $item->type = $this->type[$item->type]['name'];
        $item->status = UiService::switchTpl('status', $item,'');
        return $item;
    }

}