<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCps;
use Illuminate\Support\Facades\DB;

class CpsUserDayController extends BaseCurlController
{
    public function setModel(): ChannelCps
    {
        return $this->model = new ChannelCps();
    }

    public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
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
        ];
    }

    public function setListOutputItemExtend($item)
    {
        $item->level = $item->pid > 0 ? '二级' : '一级';
        $item->share_amount = number_format($item->share_amount, 2, '.', '');
        $item->share_ratio = $item->share_ratio . '%';
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
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '渠道',
                'default' => '',
                'data' => $this->getCpsChannels()
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
                'name' => '时间范围',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

}