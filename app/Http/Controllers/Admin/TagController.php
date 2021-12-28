<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;

class TagController extends BaseCurlController
{

    public $pageName = '标签';

    public function setModel()
    {
        return $this->model = new Tag();
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
                'width' => 80,
                'title' => '排序',
                'sort' => 1,
                'align' => 'center',
                'edit' => 1
            ],
            [
                'field' => 'hits',
                'minWidth' => 80,
                'title' => '点击数',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'name',
                'minWidth' => 150,
                'title' => '标签名称',
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

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'name',
                'type' => 'text',
                'name' => '标签名称',
                'must' => 1,
                'default' => '',
            ],
            [
                'field' => 'sort',
                'type' => 'number',
                'name' => '排序',
                'must' => 0,
                'default' => 0,
            ],
        ];
        $this->uiBlade['form'] = $data;
    }

    //表单验证
    public function checkRule($id = '')
    {
        $data = [
            'name'=>'required|unique:tag,name',
        ];
        //$id值存在表示编辑的验证
        if ($id) {
            $data['name'] = 'required|unique:tag,name,' . $id;
        }
        return $data;
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'标签名称',
        ];
    }

}