<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use App\Models\ChannelDayStatistic;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;

class CpaUserDayController extends BaseCurlController
{
    use ChannelTrait;
    public function setModel(): ChannelDayStatistic
    {
        return $this->model = new ChannelDayStatistic();
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
                'field' => 'principal',
                'minWidth' => 100,
                'title' => '负责人',
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
                'field' => 'install_real',
                'minWidth' => 80,
                'title' => '真实下载人数',
                'sort' => 1,
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
                'title' => '订单数',
                'align' => 'center',
            ],
            [
                'field' => 'total_amount',
                'minWidth' => 80,
                'title' => '订单金额',
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
        if($item->channel_id ==0){
            $item->channel = '官方';
            $item->channel_code = '-';
            $item->install = round($item->install/100);
            $item->install_real = round($item->install_real/100);
            $item->unit_price = '-';
            $item->settlement_amount = '-';
        }
        $item->install = round($item->install/100);
        return $item;
    }

    public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }

    public function getCpaChannels()
    {
        $res = DB::connection('origin_mysql')->table('channels')
            ->where('status',1)
            ->where('type',0)
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
                'name' => '顶级渠道',
                'default' => '',
                'data' => $this->getTopChannels(0)
            ],
            [
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '所有渠道',
                'default' => '',
                'data' => $this->getAllChannels(0)
            ],
            [
                'field' => 'query_channel_number',
                'type' => 'text',
                'name' => '渠道码',
            ],
            [
                'field' => 'query_like_channel_principal',
                'type' => 'text',
                'name' => '负责人',
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

    public function handleResultModel($model): array
    {
        $model = $model->where('channel_type',0)->where('channel_status',1)->where('channel_id','>',0);
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);

        $date_at = $this->rq->input('query_date_at', null);
        if($date_at===null){
            $defaultDate = date('Y-m-d',strtotime('-3 month'));
            $model = $model->where('date_at','>=',$defaultDate);
        }
        $model = $model->orderBy('date_at','desc');
        /*$total = $model->count();
        $result = $model->forPage($page, $pagesize)->get();*/
        $result = $model->get();

        $totalPrice = [];
        $totalInstall = [];
        $totalInstallReal = [];
        $totalAmount = [];
        $lists = [];
        foreach ($result as $res) {
            $installValue = (int)round($res->install/100);
            $totalInstall[] = $installValue;
            $totalInstallReal[] = $res->install_real;
            $totalAmount[] = (int)$res->total_amount;
            $res->settlement_amount = round($res->unit_price * $installValue,2);
            $totalPrice[] = $res->settlement_amount;
            $lists[] = $res;
        }

        $install = array_sum($totalInstall);
        $installReal = array_sum($totalInstallReal);
        $settlement_amount = round(array_sum($totalPrice),2);
        $totalAmount = array_sum($totalAmount);
        $totalRow = [
            'install' => $install>0 ? $install : '0',
            'install_real' => $installReal>0 ? $installReal : '0',
            'settlement_amount' => $settlement_amount>0 ? $settlement_amount : '0',
            'total_amount' => $totalAmount>0 ? $totalAmount : '0',
        ];
        $offset = ($page-1)*$pagesize;
        $result = array_slice($lists,$offset,$pagesize);
        return [
            'total' => count($lists),
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