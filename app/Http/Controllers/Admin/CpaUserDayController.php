<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class CpaUserDayController extends BaseCurlController
{
    public function setModel(): ChannelCpa
    {
        return $this->model = new ChannelCpa();
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
                'field' => 'number',
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

    public function setListOutputItemExtend($item)
    {
        $item->level = $item->pid > 0 ? '二级' : '一级';
        if($item->channel_id >0){
            $info = DB::connection('origin_mysql')->table('channels')->where('id',$item->channel_id)->first();
            $item->name = $info->name;
            $item->number = $info->number;
            $item->downloads = round($item->install/100);
            $item->unit_price = $info->unit_price;
            $item->settlement_amount = round($info->unit_price * $item->downloads,2);
            $item->at_time =  date('Y-m-d',$item->created_at);
        }else{
            $item->name = '官方';
            $item->number = '-';
            $item->downloads = round($item->install/100);
            $item->unit_price = '-';
            $item->settlement_amount = '-';
            $item->at_time =  date('Y-m-d',$item->created_at);
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
                'field' => 'query_at_time',
                'type' => 'date',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '时间范围',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    #[ArrayShape(['total' => "mixed", 'result' => "array"])] public function handleResultModel($model): array
    {
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $order_by_name = $this->orderByName();
        $order_by_type = $this->orderByType();
        $model = $this->orderBy($model, $order_by_name, $order_by_type);
        //$total = $model->count();
        $result = $model->forPage($page, $pagesize)->get();
        $handleLists = [];
        $total = 0;
        foreach ($result as $res) {
            if ($res->channel_id > 0) {
                ++$total;
                $handleLists[] = $res;
            }
        }
        $result = $handleLists;
        return [
            'total' => $total,
            'result' => $result
        ];
    }
}