<?php

namespace App\Http\Controllers\Admin;

use App\Models\Channel;
use App\Models\Users;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

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
                'title' => '账号(渠道码)',
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
                'field' => 'unit_price',
                'minWidth' => 80,
                'title' => '客单价',
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
            [
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];
    }

    public function setOutputUiCreateEditForm($show = '')
    {
        if($show && (admin('account')==$show->number)){ //不能编辑自己
            $data = [
                [
                    'field' => 'name',
                    'type' => 'text',
                    'name' => '渠道名称',
                    'must' => 1,
                    'attr' => 'readonly',
                    'default' => '',
                ],
                [
                    'field' => 'promotion_code',
                    'type' => 'text',
                    'name' => '推广码',
                    'attr' => 'readonly',
                    'must' => 1,
                ],
            ];
        }else{
            $data = [
                [
                    'field' => 'name',
                    'type' => 'text',
                    'name' => '渠道名称',
                    'must' => 1,
                    'attr' => !empty($show) ? 'readonly' : '',
                    'default' => '',
                ],
                [
                    'field' => 'promotion_code',
                    'type' => 'text',
                    'name' => '推广码',
                    'attr' => !empty($show) ? 'readonly' : '',
                    'must' => 1,
                ],
                [
                    'field' => 'unit_price',
                    'type' => 'text',
                    'name' => '单价',
                    'must' => 0,
                ],
            ];
        }
        
        //赋值给UI数组里面,必须是form为key
        $this->uiBlade['form'] = $data;
    }

    public function writeChannelDeduction($id, $deduction=5000, $date=null)
    {
        $insertData = [
            'channel_id' => $id,
            'deduction' => $deduction,
            'created_at' =>$date ?? date('Y-m-d H:i:s'),
        ];
        DB::connection('origin_mysql')->table('statistic_channel_deduction')->insert($insertData);
    }

    public function afterSaveSuccessEvent($model, $id = '')
    {
        if($id == ''){ //添加
            $parentChannelNumber = admin('account');
            $parentChannelInfo = $this->model->where('number',$parentChannelNumber)->first();
            $promotion_code = $this->rq->input('promotion_code') ?? $parentChannelInfo->promotion_code;
            $model->name = $this->rq->input('name');
            $model->principal = $parentChannelInfo->principal;
            $model->promotion_code = $promotion_code;
            $model->pid = $parentChannelInfo->id;
            $model->number = 'S'.Str::random(6) . $model->id;
            $model->type = $parentChannelInfo->type;
            $model->status = $parentChannelInfo->status;
            $model->deduction = $parentChannelInfo->deduction;
            $model->is_deduction = $parentChannelInfo->is_deduction;
            $model->unit_price = $this->rq->input('unit_price') ?? $parentChannelInfo->unit_price;
            $model->share_ratio = $parentChannelInfo->share_ratio;
            $model->level_one = $parentChannelInfo->level_one;
            $model->level_two = $parentChannelInfo->level_two;
            //
            $one = DB::connection('origin_mysql')->table('domain')->where('status',1)->inRandomOrder()->first();
            $model->url = match ($model->type) {
                1 => $one->name . '/downloadFast?' . http_build_query(['channel_id' => $promotion_code]),
                0,2 => $one->name . '?' . http_build_query(['channel_id' => $promotion_code]),
            };
            //$model->statistic_url = env('RESOURCE_DOMAIN') . '/channel/index.html?' . http_build_query(['code' => $model->number]);
            //https://sao.yinlian66.com/channel/index.html?code=1
            //创建账号
            $insertChannelAccount = [
                'nickname' => $model->name,
                'account' => $model->number,
                'password' => bcrypt($model->number),
                'created_at' => time(),
                'updated_at' => time(),
            ];
            $rid = DB::table('admins')->insertGetId($insertChannelAccount);
            DB::table('model_has_roles')->insert([
                'role_id' => 3,
                'model_id' => $rid,
                'model_type' => 'admin',
            ]);
            $model->save();

            $this->writeChannelDeduction($model->id,$model->deduction,$model->updated_at);
            $insertData = [
                'channel_name' => $model->name,
                'channel_id' => $model->id,
                'channel_pid' => $model->pid,
                'channel_promotion_code' => $model->promotion_code,
                'channel_code' => $model->number,
                'unit_price' => $model->unit_price,
                'share_ratio' => $model->share_ratio,
                'total_recharge_amount' => 0,
                'total_amount' => 0,
                'total_orders' => 0,
                'order_index' => 0,
                'usage_index' => 0,
                'share_amount' => 0,
                'date_at' => date('Y-m-d'),
            ];
            
            DB::connection('origin_mysql')->table('channel_day_statistics')->insert($insertData);
        }else{
            $updateData = [
                'unit_price' => $model->unit_price
            ];
            $res = DB::connection('origin_mysql')->table('channel_day_statistics')
                ->where('channel_id',$model->id)
                ->where('date_at',date('Y-m-d'))
                ->update($updateData);
            Log::info('==channelUpdated==',[$res]);
            Cache::forget('cachedChannelById.'.$model->id);
        }
        return $model;
    }

    /*public function checkRuleData($request)
    {
        $params = $request->all();
        $name = $params['name'] ?? '';
        $one = DB::connection('origin_mysql')->table('channels')->where('name',$name)->first();
        if($one){
            //return (['code' => 1, 'msg' => lang('已有相同渠道')]);
            return (['msg' => '已有相同渠道', 'data' => [], 'code' => 1]);
        }
        return $params;
    }*/

    //表单验证
    #[ArrayShape(['name' => "string", 'promotion_code' => "string"])] public function checkRule($id = ''): array
    {
        if($id==''){
            return [
                'name'=>'required|unique:origin_mysql.channels,name',
                'promotion_code'=>'required|unique:origin_mysql.channels,promotion_code',
            ];
        }
        return [];
    }

    #[ArrayShape(['name' => "string", 'promotion_code' => "string"])] public function checkRuleFieldName($id = ''): array
    {
        if($id==''){
            return [
                'name'=>'渠道名称',
                'promotion_code'=>'推广码',
            ];
        }
        return [];
    }
    //弹窗大小
    /*public function layuiOpenWidth(): string
    {
        return '55%'; // TODO: Change the autogenerated stub
    }

    public function layuiOpenHeight(): string
    {
        return '75%'; // TODO: Change the autogenerated stub
    }*/

    public function defaultHandleBtnAddTpl($shareData)
    {
        $data = [];
        if(admin('account')!='root'){
            if ($this->isCanCreate()) {

                $data[] = [
                    'name' => '添加下级代理商',
                    'data' => [
                        'data-type' => "add"
                    ]
                ];
            }
        }
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

    public function handleResultModel($model)
    {
        $parentChannelNumber = admin('account');
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $order_by_name = $this->orderByName();
        $order_by_type = $this->orderByType();
        $model = $this->orderBy($model, $order_by_name, $order_by_type);

        if($parentChannelNumber!='root'){
            $parentChannelInfo = $this->model->where('number',$parentChannelNumber)->first();
            $agentList = [];
            $resultAll = $model->get();
            foreach ($resultAll as &$res){
                if($res->id==$parentChannelInfo->id || $res->pid==$parentChannelInfo->id){
                    $agentList[] = $res;
                }
            }
            $total = count($agentList);
            //获取当前页数据
            $offset = ($page-1)*$pagesize;
            $result = array_slice($agentList,$offset,$pagesize);
        }else{
            $total = $model->count();
            $result = $model->forPage($page, $pagesize)->get();
        }
        return [
            'total' => $total,
            'result' => $result
        ];
    }

    public function setListOutputItemExtend($item)
    {
//        $item->status = UiService::switchTpl('status', $item,'');
        $status = [0=>'禁用',1=>'启用'];
        $item->status = $status[$item->status];
        $item->type += 0;
        $item->type = $this->channelType[$item->type]['name'];
        return $item;
    }

}