<?php


namespace App\Http\Controllers\Admin;


use App\Models\LoginLog;

class LoginLogController extends BaseCurlIndexController
{
    //设置页面的名称
    public $pageName = '会员登录日志记录';

    public function setModel()
    {
        return $this->model = new LoginLog();
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
                'width' => 100,
                'title' => '用户ID',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'account',
                'minWidth' => 100,
                'title' => '账号',
                'align' => 'center',
            ],
            [
                'field' => 'nickname',
                'minWidth' => 100,
                'title' => '用户昵称',
                'align' => 'center',
            ],
            [
                'field' => 'ip',
                'minWidth' => 150,
                'title' => '登录IP',
                'align' => 'center',

            ],
            [
                'field' => 'area',
                'minWidth' => 150,
                'title' => '登录地理位置',
                'align' => 'center',
            ],
            [
                'field' => 'version',
                'minWidth' => 100,
                'title' => '版本',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'device_info',
                'minWidth' => 100,
                'title' => '设备信息',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'source_info',
                'minWidth' => 100,
                'title' => '源信息',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'created_at',
                'minWidth' => 100,
                'title' => '登录时间',
                'align' => 'center'
            ]
        ];

        return $cols;
    }

    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            /*[
                'field' => 'id',
                'type' => 'text',
                'name' => 'ID',
            ],*/
            [
                'field' => 'query_like_account',
                'type' => 'text',
                'name' => '会员账号',
            ],
            [
                'field' => 'query_like_ip',
                'type' => 'text',
                'name' => '登录ip',
            ],

        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

}