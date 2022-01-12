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

namespace App\TraitClass;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait QueryWhereTrait
{
    //开头格式必须是 whereBy,后面是驼峰形式编写
    public function whereByQueryLikeNickname($value)
    {
        $data = [
            //'nickname'表示字段
            'nickname' => [
                'type' => 'like',//搜索条件类型
                'value' => $value //搜索值
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByQueryLikeAccount($value)
    {
        $data = [
            'account' => [
                'type' => 'like',
                'value' => $value
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByQueryLikeName($value)
    {
        $data = [
            'name' => [
                'type' => 'like',
                'value' => $value
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByQueryLikeNumber($value)
    {
        $data = [
            'number' => [
                'type' => 'like',
                'value' => $value
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByQueryLikeChannelCode($value)
    {
        $data = [
            'channel_code' => [
                'type' => 'like',
                'value' => $value
            ],
        ];
        $this->addWhere($data);
    }

    public function whereByQueryPhoneNumber($value)
    {
        if($value!==''){
            $data = [
                'phone_number' => [
                    'type' => $value==1 ? '>' : '=',
                    'value' => 0
                ]
            ];
            $this->addWhere($data);
        }

    }

    public function whereByQueryChannelId($value)
    {
        if($value!==''){
            $data = [
                'channel_id' => [
                    'type' => '=',
                    'value' => $value
                ],
                'pid' => [
                    'type' => 'or',
                    'value' => $value
                ]
            ];
            $this->addWhere($data);
        }

    }

    public function whereByQueryChannelNumber($value)
    {
        if($value!==''){
            $channelId = DB::connection('origin_mysql')->table('channels')->where('number',$value)->value('id');
            $data = [
                'channel_id' => [
                    'type' => '=',
                    'value' => $channelId
                ],
            ];
            $this->addWhere($data);
        }

    }

    public function whereByQueryDateAt($value)
    {
        $dateArr = explode('~',$value);
        if(isset($dateArr[0]) && isset($dateArr[1])){
            $data1 = [
                'date_at' => [
                    'type' => 'between',
                    'value' => [trim($dateArr[0]),trim($dateArr[1])]
                ]
            ];
            $this->addWhere($data1);
        }

    }

    public function whereByQueryAtTime($value)
    {
        $dateArr = explode('~',$value);
        if(isset($dateArr[0]) && isset($dateArr[1])){
            $data1 = [
                'at_time' => [
                    'type' => 'between',
                    'value' => [strtotime($dateArr[0]),strtotime($dateArr[1])]
                ]
            ];
            $this->addWhere($data1);
        }

    }

    public function whereByQueryIsChecked($value)
    {
        $data = [
            //'is_checked'表示字段
            'is_checked' => [
                'type' => '=',//搜索条件类型
                'value' => $value //搜索值
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByQueryStatus($value)
    {
        $data = [
            'status' => [
                'type' => '=',//搜索条件类型
                'value' => $value //搜索值
            ]
        ];
        $this->addWhere($data);
    }

    public function whereByID($value)
    {
        $data = [
            'id' => [
                'type' => '=',
                'value' => $value
            ]
        ];
        $this->unsetAllWhere();
        $this->addWhere($data);
    }

}
