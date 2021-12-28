<?php

namespace App\Http\Controllers\Admin;

use App\Models\Domain;
use App\Services\UiService;

class DomainController extends BaseCurlController
{
    public $pageName = '域名管理';

    //0-官网域名,1-渠道域名,2-接口域名
    public $domainType = [
        [
            'id' => '0',
            'name' => '官网域名'
        ],
        [
            'id' => '1',
            'name' => '渠道域名'
        ],
        [
            'id' => '2',
            'name' => '接口域名'
        ]
    ];

    public function setModel()
    {
        return $this->model = new Domain();
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
                'field' => 'name',
                'minWidth' => 150,
                'title' => '域名',
                'align' => 'center',
            ],
            [
                'field' => 'type',
                'width' => 150,
                'title' => '类型',
                'align' => 'center',
                'hide' => false
            ],
            [
                'field' => 'status',
                'minWidth' => 80,
                'title' => '是否启用',
                'align' => 'center',
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

    public function setListOutputItemExtend($item)
    {
        $item->status = UiService::switchTpl('status', $item);
        $item->type = $this->domainType[$item->type]['name'];
        return $item;
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'name',
                'type' => 'text',
                'name' => '域名',
                'must' => 1,
                'default' => '',
            ],
            [
                'field' => 'type',
                'type' => 'radio',
                'name' => '域名类型',
                'verify' => '',
                'default' => 0,
                'data' => $this->domainType
            ],
            [
                'field' => 'status',
                'type' => 'radio',
                'name' => '是否启用',
                'verify' => '',
                'default' => 1,
                'data' => $this->uiService->trueFalseData()
            ],
        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }

}