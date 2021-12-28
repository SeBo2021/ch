<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlackList;

class BlackListController extends BaseCurlController
{
    //设置页面的名称
    public $pageName = '会员';

    //1.设置模型
    public function setModel()
    {
        return $this->model = new BlackList();
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
                'must' => 0,
                'default' => '',
            ],

        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }
}