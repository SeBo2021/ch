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
            $name = $info->name ?? '被删除';
            $number = $info->number ?? '';
            $unit_price = $info->unit_price ?? 0;
            $item->name = $name;
            $item->number = $number;
            $item->downloads = round($item->install/100);
            $item->unit_price = $unit_price;
            $item->settlement_amount = round($unit_price * $item->downloads,2);
        }else{
            $item->name = '官方';
            $item->number = '-';
            $item->downloads = round($item->install/100);
            $item->unit_price = '-';
            $item->settlement_amount = '-';
        }
        $item->at_time =  date('Y-m-d',$item->at_time);
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
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '渠道',
                'default' => '',
                'data' => $this->getCpaChannels()
            ],
            [
                'field' => 'query_channel_number',
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
        //$result = $model->forPage($page, $pagesize)->get();
        /*$fields = 'id,pid,channel_id,at_time,SUM(access) as access,
                SUM(hits) as hits,
                SUM(install) as install,
                SUM(register) as register';
        $result = $model->select(DB::raw($fields))->groupBy('channel_id')->get();*/
        $result = $model->get();
        $handleLists = [];
        foreach ($result as $res) {
            $type = DB::connection('origin_mysql')->table('channels')->where('id',$res->channel_id)->value('type');
            if ($res->channel_id > 0 && $type!=2) {
                if(isset($handleLists[$res->channel_id])){
                    $handleLists[$res->channel_id]->install += $res->install;
                }else{
                    $handleLists[$res->channel_id] = $res;
                }
            }
        }
        $result = array_values($handleLists);
        $total = count($result);
        //获取当前页数据
        $offset = ($page-1)*$pagesize;
        $currentPageData = array_slice($result,$offset,$pagesize);
        return [
            'total' => $total,
            'result' => $currentPageData
        ];
    }
}