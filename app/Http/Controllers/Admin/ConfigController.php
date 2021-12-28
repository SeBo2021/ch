<?php
// +----------------------------------------------------------------------
// | KQAdmin [ 基于Laravel后台快速开发后台 ]
// | 快速laravel后台管理系统，集成了，图片上传，多图上传，批量Excel导入，批量插入，修改，添加，搜索，权限管理RBAC,验证码，助你开发快人一步。
// +----------------------------------------------------------------------
// | Copyright (c) 2012~2019 www.haoxuekeji.cn All rights reserved.
// +----------------------------------------------------------------------
// | Laravel 原创视频教程，文档教程请关注 www.heibaiketang.com
// +----------------------------------------------------------------------
// | Author: kongqi <531833998@qq.com>`
// +----------------------------------------------------------------------

namespace App\Http\Controllers\Admin;

use App\Jobs\ProcessVideoViewTimes;
use App\Models\Config;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

class ConfigController extends BaseCurlController
{
    //去掉公共模板
    public $commonBladePath = '';
    public $pageName = '基本配置';

    public function indexShareData()
    {
        $config_name = \request()->input('group_type', 'config');
        $config = config_cache($config_name);
        $config = is_array($config) ? $config : [];

        $this->setOutputUiCreateEditForm($config);

    }

    //4.编辑和添加页面表单数据
    public function setOutputUiCreateEditForm($show = '')
    {

        $data = [
            [
                'group_name' => '基础设置',
                'data' => [
                    [
                        'field' => 'group_type',
                        'type' => 'hidden',
                        'name' => '配置类型',
                        'value' => 'config'
                    ],
                    [
                        'field' => 'logo',
                        'type' => 'img',
                        'name' => 'LOGO',
                        'must' => 1,
                        'verify' => ''
                    ],
                    [
                        'field' => 'name',
                        'type' => 'text',
                        'name' => '名称',
                        'must' => 1,
                        'verify' => ''
                    ],
                    [
                        'field' => 'map',
                        'type' => 'map',
                        'name' => '地图坐标',
                        'must' => 0,
                        'verify' => '',
                        'default'=>'',
                        'mark' => ''
                    ],
                    [
                        'field' => 'cache_version',
                        'type' => 'text',
                        'name' => '后台缓存版本号',
                        'must' => 1,
                        'verify' => 'rq',
                        'default' => '1.0'
                    ],
                    [
                        'field' => 'content',
                        'type' => 'editor',
                        'editor_type' => '',//编辑器类型指定，默认是
                        'name' => '协议',
                        'must' => 1,
                        'verify' => '',
                        'id' => 'content'
                    ],
                    [
                        'field' => 'intro',
                        'type' => 'editor',
                        'editor_type' => 'simple',//编辑器类型指定，默认是
                        'name' => '介绍',
                        'must' => 1,
                        'verify' => '',
                        'tips' => '我支持simple编辑器,还支持截图上传'

                    ],
                    [
                        'field' => 'is_checked',
                        'type' => 'radio',
                        'name' => '是否启用',
                        'verify' => '',
                        'default' => 1,
                        'data' => $this->uiService->trueFalseData()
                    ]
                ]
            ],
            [
                'group_name' => '上传设置',
                'data' => [
                    [
                        'field' => 'upload_size',
                        'type' => 'text',
                        'name' => '文件上传最大大小',
                        'must' => 1,
                        'verify' => 'number',
                        'mark' => '单位(M)'
                    ]

                ]
            ],
            /*[
                'group_name' => 'APP包设置',
                'data' => [
                    [
                        'field' => 'app_id',
                        'type' => 'text',
                        'name' => '应用标识(AppId)',
                        'must' => 1,
                        'verify' => 'rq',
                        'default' => '',
                    ],
                    [
                        'field' => 'app_name',
                        'type' => 'text',
                        'name' => '应用名称',
                        'must' => 1,
                        'verify' => 'rq',
                        'default' => '',
                    ],
                    [
                        'field' => 'app_version_name',
                        'type' => 'text',
                        'name' => '应用版本名称',
                        'must' => 1,
                        'verify' => 'rq',
                        'default' => '1.0',
                        'tips' => '作为升级较验使用'
                    ],
                    [
                        'field' => 'app_version_code',
                        'type' => 'text',
                        'must' => 1,
                        'name' => '应用版本号',
                        'verify' => 'rq',
                        'default' => '1',
                        'tips' => '1'
                    ],
                    [
                        'field' => 'app_update_content',
                        'type' => 'editor',
                        'editor_type' => 'simple',//编辑器类型指定，默认是
                        'name' => '更新内容',
                        'must' => 1,
                        'tips' => '',
                        'default' => ''
                    ],
                    [
                        'field' => 'app_update_url',
                        'type' => 'text',
                        'must' => 1,
                        'name' => '应用更新地址',
                        'verify' => 'rq',
                        'default' => '',
                        'tips' => 'https://'
                    ],

                ]
            ],*/
            /*[
                'group_name' => '参数设置',
                'data' => [
                    [
                        'field' => 'free_view_long_video_times',
                        'type' => 'number',
                        'name' => '免费观看次数',
                        'must' => 1,
                        'verify' => 'rq',
                        'default' => '',
                    ]

                ]
            ]*/

        ];

        //赋值到ui数组里面必须是`formShowBtn`的key值,这里会显示按钮图标
        $this->uiBlade['formShowBtn'] = $data;
        $this->uiBlade['show'] = $show;

    }

    public function setModel()
    {
        return new Config();
    }

    public function store(Request $request)
    {
        $config_name = $request->input('group_type', 'config');
        $config_values = $request->except(['_token','s']);
        config_cache($config_name, $config_values);
        //
        $this->insertLog(lang('系统配置成功'));
        return $this->returnSuccessApi('设置成功');

    }

    //去掉按钮
    public function setOutputHandleBtnTpl($shareData)
    {
        return $this->uiBlade['btn'] = [];
    }

}
