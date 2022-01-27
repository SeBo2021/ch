<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use App\Models\ChannelCps;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;

class ChannelUserDayController extends BaseCurlController
{
    public $channelInfo;

    public $adminAccount;

    public function setModel()
    {
        /*$adminAccount = $this->getAdminAccount();
        $this->channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',$adminAccount)->first();
        $type = $this->channelInfo ? $this->channelInfo->type : 2;
        return match ($type) {
            0 => $this->model = new ChannelCpa(),
            2 => $this->model = new ChannelCps(),
        };*/
        return $this->model = new ChannelCps();
    }

    public function getModel()
    {
        $adminAccount = admin('account');
        $this->adminAccount = $adminAccount;
        if($adminAccount!='root'){
            $this->channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',$adminAccount)->first();
            $type = $this->channelInfo ? $this->channelInfo->type : 2;
            return match ($type) {
                0 => new ChannelCpa(),
                2 => new ChannelCps(),
            };
        }
        return $this->model;
    }

    public function getCpaIndexCols(): array
    {
        return [
            [
                'type' => 'checkbox',
                'totalRowText' => '合计',
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
                'align' => 'center'
            ],
            [
                'field' => 'level',
                'minWidth' => 100,
                'title' => '级数',
                'align' => 'center'
            ],
            [
                'field' => 'number',
                'minWidth' => 80,
                'title' => '渠道码',
//                'hide' => true,
                'align' => 'center',
            ],

            [
                'field' => 'install',
                'minWidth' => 80,
                'title' => '今日下载人数',
                'align' => 'center',
            ],
            [
                'field' => 'unit_price',
                'minWidth' => 80,
                'title' => '单价(¥)',
                'align' => 'center',
            ],
            [
                'field' => 'settlement_amount',
                'minWidth' => 80,
                'title' => '结算金额(¥)',
                'align' => 'center',
            ],
            [
                'field' => 'at_time',
                'minWidth' => 150,
                'title' => '统计日期',
                'align' => 'center'
            ],
        ];
    }

    public function getCpsIndexCols(): array
    {
        return [
            [
                'type' => 'checkbox',
                'totalRowText' => '合计',
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
                'align' => 'center'
            ],
            [
                'field' => 'level',
                'minWidth' => 100,
                'title' => '级数',
                'align' => 'center'
            ],
            [
                'field' => 'channel_code',
                'minWidth' => 80,
                'title' => '渠道码',
//                'hide' => true,
                'align' => 'center',
            ],
            [
                'field' => 'promotion_code',
                'minWidth' => 100,
                'title' => '推广码',
                'hide' => true,
                'align' => 'center'
            ],

            [
                'field' => 'orders',
                'minWidth' => 80,
                'title' => '订单',
                'align' => 'center',
            ],
            [
                'field' => 'total_recharge_amount',
                'minWidth' => 80,
                'title' => '总充值金额',
                'align' => 'center',
            ],
            [
                'field' => 'share_ratio',
                'minWidth' => 80,
                'title' => '分成比例',
                'align' => 'center',
            ],
            [
                'field' => 'share_amount',
                'minWidth' => 80,
                'title' => '分成金额',
                'align' => 'center',
            ],
            [
                'field' => 'date_at',
                'minWidth' => 150,
                'title' => '统计日期',
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

    #[Pure] public function indexCols(): array
    {
        $type = $this->channelInfo ? $this->channelInfo->type : 2;
        return match ($type) {
            0 => $this->getCpaIndexCols(),
            2 => $this->getCpsIndexCols(),
        };
//        return $this->channelInfo->type;
    }

    public function handleResultModel($model)
    {
        $parentChannelNumber = admin('account');
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $order_by_name = $this->orderByName();
        $order_by_type = $this->orderByType();
        $model = $this->orderBy($model, $order_by_name, $order_by_type);
        //$total = $model->count();
        $result = $model->get();
        if($parentChannelNumber!='root' && $this->channelInfo){
            $handleLists = [];
//            $channelBuild = DB::connection('origin_mysql')->table('channels')->where();
            if($this->channelInfo->type == 0){ //cpa
                $settlement_amount = 0;
                $channelsBuild = DB::connection('origin_mysql')->table('channels');
                foreach ($result as $res){
                    if(($res->channel_id==$this->channelInfo->id) || ($res->pid==$this->channelInfo->id)){
                        $channelInfo = $channelsBuild->where('id',$res->channel_id)->first();
                        $res->install = (int)round($res->install/100);
                        $channelInfo->unit_price = $channelInfo->unit_price??0;
                        $res->settlement_amount = round($channelInfo->unit_price * $res->install,2);
                        $res->unit_price = $channelInfo->unit_price;
                        $res->name = $channelInfo->name;
                        $res->number = $channelInfo->number;
                        $settlement_amount += (int)$res->settlement_amount;
                        if(isset($handleLists[$res->channel_id])){
                            $handleLists[$res->channel_id.'-'.$res->at_time]->install += $res->install;
                        }else{
                            $handleLists[$res->channel_id.'-'.$res->at_time] = $res;
                        }
                    }
                }
                $totalRow = [
                    'settlement_amount' => $settlement_amount
                ];
            }else{
                $total_recharge_amount = 0;
                foreach ($result as $res){
                    if(($res->channel_id==$this->channelInfo->id) || ($res->pid==$this->channelInfo->id)){
                        $handleLists[] = $res;
                        $total_recharge_amount += $res->share_amount;
                    }
                }
                $totalRow = [
                    'total_recharge_amount' => number_format($total_recharge_amount, 2, '.', '')
                ];
            }
            $result = $handleLists;
            //Log::info('===CPADATA===',[$this->channelInfo,$parentChannelNumber]);
            $total = count($result);
            //获取当前页数据
            $offset = ($page-1)*$pagesize;
            $currentPageData = array_slice($result,$offset,$pagesize);
            return [
                'total' => $total,
                'totalRow' => $totalRow ?? [],
                'result' => $currentPageData
            ];
        }
        return ['total' => 0, 'result' => []];

    }

    public function setListOutputItemExtend($item)
    {
        $item->level = $item->pid > 0 ? '二级' : '一级';
        switch ($this->channelInfo->type) {
            case 2:
                $item->share_amount = number_format($item->share_amount, 2, '.', '');
                $item->share_ratio = $item->share_ratio . '%';
                break;
            case 0:
                //$info = DB::connection('origin_mysql')->table('channels')->where('id',$item->channel_id)->first();
                //$item->name = $info->name;
                //$item->number = $info->number;
                //$item->downloads = round($item->install/100);
                //$item->unit_price = $info->unit_price;
                //$item->settlement_amount = round($info->unit_price * $item->downloads,2);
                $item->at_time =  date('Y-m-d',$item->at_time);
                break;
        }

        return $item;
    }

    public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }

    public function setOutputSearchFormTpl($shareData)
    {
        $data = [];
        switch ($this->channelInfo->type) {
            case 2:
                $data = [
                    [
                        'field' => 'query_like_channel_code',
                        'type' => 'text',
                        'name' => '渠道码',
                    ],
                    [
                        'field' => 'query_date_at',
                        'type' => 'date',
                        'attr' => 'data-range=~',//需要特殊分割
                        'name' => '时间范围',
                    ]
                ];
                break;
            case 0:
                $data = [
                    [
                        'field' => 'query_like_channel_code',
                        'type' => 'text',
                        'name' => '渠道码',
                    ],
                    [
                        'field' => 'query_at_time',
                        'type' => 'date',
                        'attr' => 'data-range=~',//需要特殊分割
                        'name' => '时间范围',
                    ]
                ];
                break;
        }
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    //首页共享数据
    public function indexShareData()
    {
        //设置首页数据替换
        $this->setListConfig(['open_width' => '600px', 'open_height' => '700px','tableConfig' => ['totalRow' => true]]);
    }

}