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

    public function setModel(): ChannelCps
    {
        $this->channelInfo = DB::connection('origin_mysql')->table('channels')->where('number',admin('account'))->first();
        $type = $this->channelInfo ? $this->channelInfo->type : 2;
        return match ($type) {
            0 => $this->model = new ChannelCpa(),
            2 => $this->model = new ChannelCps(),
        };
    }

    public function getCpaIndexCols()
    {
        return [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
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
                'field' => 'downloads',
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
                'type' => 'checkbox'
            ],
            [
                'field' => 'name',
                'minWidth' => 100,
                'title' => '渠道名称',
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
        if($parentChannelNumber!='root'){
            $parentChannelInfo = $this->model->where('channel_code',$parentChannelNumber)->first();
            $id = $parentChannelInfo ? $parentChannelInfo->id : 0;
            if($parentChannelInfo){
                $model = $this->orderBy($this->model->where('id',$id)->orWhere('pid',$id), $order_by_name, $order_by_type);
            }else{
                return ['total' => 0, 'result' => []];
            }
        }else{
            $model = $this->orderBy($model, $order_by_name, $order_by_type);
        }
        $total = $model->count();
        $result = $model->forPage($page, $pagesize)->get();
        return [
            'total' => $total,
            'result' => $result
        ];
    }

    public function setListOutputItemExtend($item)
    {
        switch ($this->channelInfo->type) {
            case 2:
                $item->share_amount = number_format($item->share_amount, 2, '.', '');
                $item->share_ratio = $item->share_ratio . '%';
                break;
            case 0:
                $info = DB::connection('origin_mysql')->table('channel')->where('id',$item->channel_id)->first();
                $item->name = $info->name;
                $item->channel_code = $info->number;
                $item->downloads = $info->install;
                $item->unit_price = $info->unit_price;
                $item->settlement_amount = round($info->unit_price * $info->install,2);
                $item->at_time =  date('Y-m-d',$info->created_at);
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
        $data = [
            [
                'field' => 'query_like_channel_code',
                'type' => 'text',
                'name' => '渠道码',
            ],
        ];
        switch ($this->channelInfo->type) {
            case 2:
                $data[] = [
                    'field' => 'query_date_at',
                    'type' => 'date',
                    'attr' => 'data-range=~',//需要特殊分割
                    'name' => '时间范围',
                ];
                break;
            case 0:
                $data[] = [
                    'field' => 'query_at_time',
                    'type' => 'date',
                    'attr' => 'data-range=~',//需要特殊分割
                    'name' => '时间范围',
                ];
                break;
        }
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

}