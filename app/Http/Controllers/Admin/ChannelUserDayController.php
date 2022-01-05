<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCps;
use Illuminate\Support\Facades\Log;

class ChannelUserDayController extends BaseCurlController
{
    public function setModel(): ChannelCps
    {
        return $this->model = new ChannelCps();
    }

    public function indexCols(): array
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

    public function handleResultModel($model)
    {
        $parentChannelNumber = admin('account');
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $order_by_name = $this->orderByName();
        $order_by_type = $this->orderByType();
        if($parentChannelNumber!='root'){
            $parentChannelInfo = $this->model->where('channel_code',$parentChannelNumber)->first();
            $model = $this->orderBy($this->model->where('id',$parentChannelInfo?$parentChannelInfo->id:0)->orWhere('pid',$parentChannelInfo->id), $order_by_name, $order_by_type);
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
        $item->share_amount = number_format($item->share_amount, 2, '.', '');
        $item->share_ratio = $item->share_ratio . '%';
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
            ],
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

}