<?php

namespace App\Http\Controllers\Admin;

use App\Models\Channel;
use App\Services\UiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChannelsController extends BaseCurlController
{
    public $pageName = '渠道';

    public array $isDeduction = [
        1 => ['id' => 1, 'name' => '开'],
        0 => ['id' => 0, 'name' => '关'],
    ];

    public array $channelType = [
        0 => [
            'id' => 0,
            'name' => 'CPA'
        ],
        1 => [
            'id' => 1,
            'name' => '包月'
        ],
        2 => [
            'id' => 2,
            'name' => 'CPS'
        ],
    ];

    public function setModel(): Channel
    {
        return $this->model = new Channel();
    }

    public function indexCols(): array
    {
        return [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'number',
                'minWidth' => 80,
                'title' => '账号',
//                'hide' => true,
                'align' => 'center',
            ],
            /*[
                'field' => 'type',
                'minWidth' => 100,
                'title' => '渠道类型',
                'align' => 'center'
            ],*/
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
                'align' => 'center'
            ],
            [
                'field' => 'promotion_code',
                'minWidth' => 100,
                'title' => '推广码',
                //'edit' => 1,
                'align' => 'center'
            ],

            [
                'field' => 'url',
                'minWidth' => 80,
                'title' => '渠道推广链接',
                'align' => 'center',
            ],
            [
                'field' => 'statistic_url',
                'minWidth' => 80,
                'title' => '统计链接地址',
                'align' => 'center',
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
                'hide' => true,
                'align' => 'center'
            ],
            /*[
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]*/
        ];
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        $data = [
            [
                'field' => 'password',
                'type' => 'text',
                'name' => '密码',
                // 'remove'=>$show?'1':0,//1表示移除，编辑页面不出现
                'value' => '',
                'mark' => $show ? '不填表示不修改密码' : '',
            ],
            [
                'field' => 'name',
                'type' => 'text',
                'name' => '渠道名称',
                'must' => 1,
                'default' => '',
            ],
            [
                'field' => 'promotion_code',
                'type' => 'text',
                'name' => '推广码',
                'must' => 1,
            ],
            [
                'field' => 'type',
                'type' => 'radio',
                'name' => '类型',
                'must' => 0,
                'default' => 0,
                'verify' => 'rq',
                'data' => $this->channelType
            ],
            [
                'field' => 'deduction',
                'type' => 'number',
                'name' => '扣量(点) (CPA使用)',
                'value' => ($show && ($show->deduction>0)) ? $show->deduction/100 : 50,
                'must' => 0,
                'default' => '50',
            ],
            [
                'field' => 'unit_price',
                'type' => 'text',
                'name' => '单价 (CPA使用)',
                'must' => 0,
            ],
            [
                'field' => 'is_deduction',
                'type' => 'radio',
                'name' => '前10个下载不扣量 (CPA使用)',
                'default' => 0,
                'data' => $this->isDeduction
            ],
            /*[
                'field' => 'deduction_period',
                'type' => 'text',
                'event' => 'timeRange',
                'name' => '扣量时间段 (CPS使用)',
                'must' => 0,
                'attr' => 'data-format=HH:mm:ss data-range=~',//需要特殊分割
                'default' => '00:00:00 ~ 23:59:59',
            ],*/
            [
                'field' => 'level_one',
                'type' => 'text',
                'name' => '一阶 (CPS使用)',
                'tips' => '1-10单【填1表示扣第1单，如填1,3表示扣第一单和第三单，不填不扣】',
            ],
            [
                'field' => 'level_two',
                'type' => 'number',
                'name' => '二阶 (CPS使用)',
                'tips' => '11单及以上【填间隔值：填1，表示扣第12，14，16..类推，2表示扣第13,16,19..】',
            ],
            [
                'field' => 'share_ratio',
                'type' => 'number',
                'name' => '分成比例 (CPS使用)',
                'default' => '',
            ],
        ];
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }

    public function beforeSaveEvent($model, $id = '')
    {
        $model->status = 1;
        $model->deduction *= 100;
        if($id>0){ //编辑
            if($model->deduction>0){
                $originalDeduction = $model->getOriginal()['deduction'];
                if($originalDeduction != $model->deduction){
                    //dd('修改扣量');
                    $this->writeChannelDeduction($id,$model->deduction);
                }
            }
            $password = $this->rq->input('password');
            if($password){
                $exists = DB::connection('channel_mysql')->table('admins')->where('account',$model->number)->first();
                if($exists){
                    DB::connection('channel_mysql')->table('admins')->where('account',$model->number)->update(['password'=>bcrypt($password)]);
                }else{
                    $this->createChannelAccount($model,bcrypt($password));
                }
            }
        }
    }

    public function writeChannelDeduction($id, $deduction=5000, $date=null)
    {
        $insertData = [
            'channel_id' => $id,
            'deduction' => $deduction,
            'created_at' =>$date ?? date('Y-m-d H:i:s'),
        ];
        DB::table('statistic_channel_deduction')->insert($insertData);
    }

    public function createChannelAccount($model,$password='')
    {
        $insertChannelAccount = [
            'nickname' => $model->name,
            'account' => $model->number,
            'password' => $password,
            'created_at' => time(),
            'updated_at' => time(),
        ];
        $rid = DB::connection('channel_mysql')->table('admins')->insertGetId($insertChannelAccount);
        DB::connection('channel_mysql')->table('model_has_roles')->insert([
            'role_id' => 2,
            'model_id' => $rid,
            'model_type' => 'admin',
        ]);
    }

    public function afterSaveSuccessEvent($model, $id = '')
    {
        if($id == ''){ //添加
            $model->number = 'S'.Str::random(6) . $model->id;
            //
            $one = DB::table('domain')->where('status',1)->inRandomOrder()->first();
            switch ($model->type){
                case 0:
                    $model->url = $one->name . '?'.http_build_query(['channel_id' => $model->promotion_code]);
                    break;
                case 1:
                    $model->url = $one->name . '/downloadFast?'.http_build_query(['channel_id' => $model->promotion_code]);
                    break;
            }
            $model->statistic_url = env('RESOURCE_DOMAIN') . '/channel/index.html?' . http_build_query(['code' => $model->number]);
            //https://sao.yinlian66.com/channel/index.html?code=1
            $model->save();

            $this->writeChannelDeduction($model->id,$model->deduction,$model->updated_at);
            //创建渠道用户
            $password = !empty($model->password) ? $model->password : bcrypt($model->number);
            $this->createChannelAccount($model,$password);
        }
        return $model;
    }

    public function setListOutputItemExtend($item)
    {
        $item->deduction /= 100;
        $item->status = UiService::switchTpl('status', $item,'');
        $item->type = $this->channelType[$item->type]['name'];
        return $item;
    }

    //表单验证
    public function checkRule($id = '')
    {
        $data = [
            'name'=>'required|unique:channels,name',
            'promotion_code'=>'required|unique:channels,promotion_code',
        ];
        //$id值存在表示编辑的验证
        if ($id) {
            $data['password'] = '';
            $data['name'] = 'required|unique:channels,name,' . $id;
            $data['promotion_code'] = 'required|unique:channels,promotion_code,' . $id;
        }
        return $data;
    }

    public function checkRuleFieldName($id = '')
    {
        return [
            'name'=>'渠道名称',
            'promotion_code'=>'推广码',
        ];
    }
    //弹窗大小
    public function layuiOpenWidth(): string
    {
        return '55%'; // TODO: Change the autogenerated stub
    }

    public function layuiOpenHeight(): string
    {
        return '75%'; // TODO: Change the autogenerated stub
    }

    public function defaultHandleBtnAddTpl($shareData)
    {
        $data = [];
        if ($this->isCanCreate()) {

            $data[] = [
                'name' => '添加下级代理商',
                'data' => [
                    'data-type' => "add"
                ]
            ];
        }
        /*if ($this->isCanDel()) {
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

    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            [
                'field' => 'query_like_number',
                'type' => 'text',
                'name' => '渠道码',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

}