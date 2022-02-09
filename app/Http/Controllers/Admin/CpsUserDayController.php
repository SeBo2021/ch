<?php

namespace App\Http\Controllers\Admin;


use App\Models\ChannelDayStatistic;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class CpsUserDayController extends BaseCurlController
{
    use ChannelTrait;
    public function setModel()
    {
        return $this->model = new ChannelDayStatistic();
    }

    public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }

    public function indexCols(): array
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
                'field' => 'install_real',
                'minWidth' => 80,
                'title' => '真实下载人数',
                'align' => 'center',
            ],
            [
                'field' => 'install',
                'minWidth' => 80,
                'title' => '下载人数(扣量后)',
                'align' => 'center',
            ],
            [
                'field' => 'total_orders',
                'minWidth' => 80,
                'title' => '真实订单',
                'align' => 'center',
            ],
            [
                'field' => 'total_amount',
                'minWidth' => 80,
                'title' => '真实充值金额',
                'align' => 'center',
            ],
            [
                'field' => 'orders',
                'minWidth' => 80,
                'title' => '扣量后订单',
                'align' => 'center',
            ],
            [
                'field' => 'total_recharge_amount',
                'minWidth' => 80,
                'title' => '扣量后总充值金额',
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
        ];
    }

    public function setListOutputItemExtend($item)
    {
        $item->level = $item->channel_pid > 0 ? '二级' : '一级';
        $item->share_amount = number_format($item->share_amount, 2, '.', '');
        $item->share_ratio = $item->share_ratio . '%';
        $item->install = round($item->install/100);
        return $item;
    }

    public function getCpsChannels()
    {
        $res = DB::connection('origin_mysql')->table('channels')
            ->where('status',1)
            ->where('type',2)
            ->where('pid',0)
            ->get(['id','name']);
        $data = $this->uiService->allDataArr('请选择渠道(一级)');
        foreach ($res as $item) {
            $data[$item->id] = [
                'id' => $item->id,
                'name' => $item->name,
            ];
        }
        return $data;
    }

    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            [
                'field' => 'query_channel_id_tree',
                'type' => 'select',
                'name' => '渠道',
                'default' => '',
                'data' => $this->getTopChannels(2)
            ],
            [
                'field' => 'query_like_channel_code',
                'type' => 'text',
                'name' => '渠道码',
            ],
            [
                'field' => 'query_date_at',
                'type' => 'date',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '选择日期(默认三月内)',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    #[ArrayShape(['total' => "mixed", 'totalRow' => "array", 'result' => "mixed"])] public function handleResultModel($model): array
    {
        $model = $model->where('channel_type',2)->where('channel_status',1);
        $date_at = $this->rq->input('query_date_at', null);
        if($date_at===null){
            $defaultDate = date('Y-m-d',strtotime('-3 month'));
            $model = $model->where('date_at','>=',$defaultDate);
        }
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $total = $model->count();
        $result = $model->orderBy('date_at','desc')->forPage($page, $pagesize)->get();

        $totalInstall = [];
        $totalInstallReal = [];
        $shareAmount = [];
        $totalAmount = [];
        $totalRechargeAmount = [];
        foreach ($result as $res){
            $install = (int)round($res->install/100);
            $totalInstall[] = $install;
            $totalInstallReal[] = $res->install_real;
            $shareAmount[] = $res->share_amount;
            $totalAmount[] = (int)$res->total_amount;
            $totalRechargeAmount[] = (int)$res->total_recharge_amount;
        }
        $installTotal = array_sum($totalInstall);
        $installReal = array_sum($totalInstallReal);
        $shareAmount = array_sum($shareAmount);
        $totalAmount = array_sum($totalAmount);
        $totalRechargeAmount = array_sum($totalRechargeAmount);
        $totalRow['install'] = $installTotal>0 ? $installTotal : '0';
        $totalRow['install_real'] = $installReal>0 ? $installReal : '0';
        $totalRow['share_amount'] = $shareAmount>0 ? $shareAmount : '0';
        $totalRow['total_amount'] = $totalAmount>0 ? $totalAmount : '0';
        $totalRow['total_recharge_amount'] = $totalRechargeAmount>0 ? $totalRechargeAmount : '0';
        return [
            'total' => $total,
            'totalRow' => $totalRow ?? [],
            'result' => $result
        ];
    }

    //首页共享数据
    public function indexShareData()
    {
        //设置首页数据替换
        $this->setListConfig(['open_width' => '600px', 'open_height' => '700px','tableConfig' => ['totalRow' => true]]);
    }

}