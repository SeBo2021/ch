<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UiService;
use App\TraitClass\ChannelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends BaseCurlController
{
    use ChannelTrait;
    //设置页面的名称
    public $pageName = '会员';

//    public $denyCommonBladePathActionName = ['index','create','edit'];

    //1.设置模型
    public function setModel()
    {
        return $this->model = new User();
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
                'field' => 'promotion_code',
                'minWidth' => 100,
                'title' => '推广码',
                'hide' => true,
                'align' => 'center'
            ],
            /*[
                'field' => 'mid',
                'width' => 100,
                'title' => '会员ID',
                'sort' => 1,
                'align' => 'center'
            ],*/
            [
                'field' => 'pid',
                'minWidth' => 100,
                'title' => '上级ID',
                'sort' => 1,
                'hide' => true,
                'align' => 'center',
                //'edit' => 1
            ],
            [
                'field' => 'channel_id',
                'minWidth' => 150,
                'title' => '推广渠道',
                'align' => 'center',
            ],
            [
                'field' => 'account',
                'minWidth' => 150,
                'title' => '账号',
                'align' => 'center',
            ],
            [
                'field' => 'nickname',
                'minWidth' => 150,
                'title' => '昵称',
                'align' => 'center',
            ],
            [
                'field' => 'area',
                'minWidth' => 150,
                'title' => '最近登录位置',
                'align' => 'center',
            ],
            [
                'field' => 'phone_number',
                'minWidth' => 150,
                'title' => '手机号',
                'align' => 'center',
            ],
            [
                'field' => 'long_vedio_times',
                'minWidth' => 80,
                'title' => '可观看次数',
                'align' => 'center'
            ],
            [
                'field' => 'did',
                'minWidth' => 150,
                'title' => '机器码',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'create_ip',
                'minWidth' => 150,
                'title' => '注册IP',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'last_ip',
                'minWidth' => 150,
                'title' => '最近IP',
                'align' => 'center',
                'hide' => true
            ],
            [
                'field' => 'systemPlatform',
                'minWidth' => 150,
                'title' => '手机系统平台',
                'align' => 'center',

            ],
            /*[
                'field' => 'token',
                'minWidth' => 150,
                'title' => 'token',
                'align' => 'center',
                'hide' => true
            ],*/
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
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];

        return $cols;
    }

    public function setOutputSearchFormTpl($shareData)
    {

        $data = [
            [
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '选择渠道',
                'data' => $this->getChannelSelectData()
            ],
            [
                'field' => 'query_phone_number',
                'type' => 'select',
                'name' => '是否绑定',
                'data' => $this->bindPhoneNumSelectData
            ],
            [
                'field' => 'id',
                'type' => 'text',
                'name' => '会员ID',
            ],
            [
                'field' => 'query_like_account',//这个搜索写的查询条件在app/TraitClass/QueryWhereTrait.php 里面写
                'type' => 'text',
                'name' => '账号',
            ],
            [
                'field' => 'query_created_at',
                'type' => 'datetime',
//                'attr' => 'data-range=true',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '时间范围',
            ],
            [
                'field' => 'query_status',
                'type' => 'select',
                'name' => '是否启用',
                'default' => '',
                'data' => $this->uiService->trueFalseData(1)
            ]

        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'account',
                'type' => 'text',
                'name' => '账号',
                'must' => 0,
                'verify' => 'rq',
            ],
            /*[
                'field' => 'avatar',
                'type' => 'img',
                'name' => '头像',
                'verify' => $show ? '' : 'rq',
            ],*/
            [
                'field' => 'nickname',
                'type' => 'text',
                'name' => '昵称',
                'must' => 0,
                'verify' => 'rq',
            ],
            [
                'field' => 'long_vedio_times',
                'type' => 'number',
                'name' => '可观看次数',
            ],
            [
                'field' => 'password',
                'type' => 'text',
                'name' => '密码',
                'must' => 1,
                'verify' => $show ? '' : 'rq',
                // 'remove'=>$show?'1':0,//1表示移除，编辑页面不出现
                'value' => '',
                'mark' => $show ? '不填表示不修改密码' : '',
            ],
        ];
        $this->uiBlade['form'] = $data;
    }

    public function setListOutputItemExtend($item)
    {
        $item->systemPlatform = $this->deviceSystems[$item->device_system];
        $item->channel_id = $this->getChannelSelectData(true)[$item->channel_id]['name'];
        $item->area = DB::table('login_log')->where('uid',$item->id)->orderByDesc('id')->value('area');
        $item->status = UiService::switchTpl('status', $item,'');
        $item->phone_number = $item->phone_number>0 ? $item->phone_number : '未绑定';
        return $item;
    }

    public function setOutputHandleBtnTpl($shareData)
    {
        $data = [];
        /*if ($this->isCanCreate()) {

            $data[] = [
                'name' => '添加',
                'data' => [
                    'data-type' => "add"
                ]
            ];
        }
        if ($this->isCanDel()) {
            $data[] = [
                'class' => 'layui-btn-danger',
                'name' => '删除',
                'data' => [
                    'data-type' => "allDel"
                ]
            ];
        }*/

        return $data;
    }

    //弹窗大小
    public function layuiOpenWidth()
    {
        return '55%'; // TODO: Change the autogenerated stub
    }

    public function layuiOpenHeight()
    {
        return '75%'; // TODO: Change the autogenerated stub
    }
}
