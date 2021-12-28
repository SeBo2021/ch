<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\AdminLog;
use App\Models\Role;

class LogController extends BaseCurlIndexController
{
    //页面信息
    public $pageName = '管理员操作记录';

    //1.设置模型
    public function setModel()
    {
        $this->model = new AdminLog();

    }


    //2.首页设置列表显示的信息
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
                'minWidth' => 80,
                'title' => '事件',
                'align' => 'center'
            ],
            [
                'field' => 'admin_account',
                'width' => 150,
                'title' => '管理员账号',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'admin_name',
                'width' => 150,
                'title' => '管理员昵称',
                'align' => 'center'

            ],
            [
                'field' => 'ip',
                'width' => 150,
                'title' => 'IP',
                'align' => 'center'
            ],
            [
                'field' => 'url',
                'minWidth' => 150,
                'title' => '操作页面',
                'align' => 'center'
            ],
            [
                'field' => 'created_at',
                'width' => 160,
                'title' => '操作时间',
                'align' => 'center',
            ]


        ];

        return $cols;
    }

    //3.设置搜索部分
    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            [
                'field' => 'id',
                'type' => 'text',
                'name' => 'ID',
            ],
            [
                'field' => 'query_like_admin_name',
                'type' => 'text',
                'name' => '账号',
            ],
            [
                'field' => 'query_like_url',
                'type' => 'text',
                'name' => '操作页面',
            ],
            [
                'field' => 'ip_int',
                'type' => 'text',
                'name' => '操作ip',
            ],

        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    public function setListOutputItemExtend($item)
    {
        $item->admin_account = $item->admin['account'] ?? '';
        return $item;
    }

    /*public function addListSearchWhere($model)
    {
        return $model->where('admin_id',admin('id'));
    }*/

    public function showWhere()
    {
    }
}
