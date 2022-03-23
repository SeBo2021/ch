<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use App\Models\ChannelDayStatistic;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;

class ChannelUserDayController extends BaseCurlController
{
    use ChannelTrait;

    public $channelInfo;

    public $adminAccount;

    public function setModel()
    {
        return $this->model = new ChannelDayStatistic();
    }

    public function getModel()
    {
        $adminAccount = admin('account');
        $this->adminAccount = $adminAccount;
        if($adminAccount!='root'){
            $this->channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',$adminAccount)->first();
        }
        return $this->model;
    }

    public function getCpaIndexCols(): array
    {
        $data = [
            [
                'type' => 'checkbox',
                'totalRowText' => '合计',
            ],
            [
                'field' => 'channel_name',
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
                'field' => 'install',
                'minWidth' => 80,
                'title' => '安装量',
                'align' => 'center',
            ],
            
        ];
        if($this->channelInfo->pid>0){
            $data += [
                [
                    'field' => 'agent_unit_price',
                    'minWidth' => 80,
                    'title' => '客单价',
                    'align' => 'center',
                ],
                [
                    'field' => 'agent_settlement_amount',
                    'minWidth' => 80,
                    'title' => '结算金额(¥)',
                    'align' => 'center',
                ],
            ];
        }else{
            $data += [
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
                    'field' => 'agent_unit_price',
                    'minWidth' => 80,
                    'title' => '代理单价(¥)',
                    'align' => 'center',
                ],
                [
                    'field' => 'agent_settlement_amount',
                    'minWidth' => 80,
                    'title' => '代理结算金额(¥)',
                    'align' => 'center',
                ],
            ];
        }
        $data[]= [
            'field' => 'date_at',
            'minWidth' => 150,
            'title' => '统计日期',
            'align' => 'center'
        ];
        return $data;
    }

    public function getCpsIndexCols(): array
    {
        return [
            [
                'type' => 'checkbox',
                'totalRowText' => '合计',
            ],
            [
                'field' => 'channel_name',
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
                'field' => 'channel_promotion_code',
                'minWidth' => 100,
                'title' => '推广码',
                'hide' => true,
                'align' => 'center'
            ],
            [
                'field' => 'install',
                'minWidth' => 80,
                'title' => '安装量',
                'align' => 'center',
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
            /* [
                'field' => 'date_at',
                'minWidth' => 150,
                'title' => '统计日期',
                'align' => 'center'
            ], */
            [
                'field' => 'query_date_at',
                'type' => 'date',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '选择日期(默认三月内)',
            ]
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
            0,1 => $this->getCpaIndexCols(),
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

        $date_at = $this->rq->input('query_date_at', null);
        if($date_at===null){
            $defaultDate = date('Y-m-d',strtotime('-3 month'));
            $model = $model->where('date_at','>=',$defaultDate);
        }

        $model = $this->orderBy($model, $order_by_name, $order_by_type);
        //$total = $model->count();
        //$result = $model->get();
        if($parentChannelNumber!='root' && $this->channelInfo){
            $handleLists = [];
//            $channelBuild = DB::connection('origin_mysql')->table('channels')->where();
            $channelInfoId = $this->channelInfo->id;
            $result = $model->where(function ($model) use ($channelInfoId){
                $model->where('channel_id',$channelInfoId)
                    ->orWhere('channel_pid',$channelInfoId);
            })->get();
            if($this->channelInfo->type == 0){ //cpa
                $totalPrice = [];
                $totalAgetnPrice = [];
                $totalInstall = [];
                foreach ($result as $res){
                    $install = (int)round($res->install/100);
                    $totalInstall[] = $install;
                    $res->settlement_amount = round($res->unit_price * $install,2);
                    $res->agent_settlement_amount = $res->channel_pid>0 ? round($res->agent_unit_price * $install,2) : '';
                    $totalPrice[] = $res->settlement_amount;
                    $totalAgetnPrice[] = $res->agent_settlement_amount;
                    $handleLists[] = $res;
                }
                $settlement_amount = round(array_sum($totalPrice),2);
                $agent_settlement_amount = round(array_sum($totalAgetnPrice),2);
                $installTotal = array_sum($totalInstall);
                $totalRow = [
                    'install' => $installTotal>0 ? $installTotal : '0',
                ];
                if($this->channelInfo->pid=0){
                    $totalRow['settlement_amount'] =  $settlement_amount>0 ? $settlement_amount : '0';
                }
                $totalRow['agent_settlement_amount'] = $agent_settlement_amount>0 ? $agent_settlement_amount : '0';
            }else{ //cps
                $total_recharge_amount = 0;
                $share_amount = 0;
                $totalInstall = [];
                foreach ($result as $res){
                    $handleLists[] = $res;
                    $share_amount += $res->share_amount;
                    $total_recharge_amount += $res->total_recharge_amount;
                    $install = (int)round($res->install/100);
                    $totalInstall[] = $install;
                }
                $installTotal = array_sum($totalInstall);
                $totalRow = [
                    'install' => $installTotal>0 ? $installTotal : '0',
                    'total_recharge_amount' => number_format($total_recharge_amount, 2, '.', ''),
                    'share_amount' => number_format($share_amount, 2, '.', '')
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
        $item->level = $item->channel_pid > 0 ? '二级' : '一级';
        $item->share_amount = number_format($item->share_amount, 2, '.', '');
        $item->share_ratio = $item->share_ratio . '%';
        $item->install = (int)round($item->install/100);
        return $item;
    }

    public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }

    public function setOutputSearchFormTpl($shareData)
    {
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