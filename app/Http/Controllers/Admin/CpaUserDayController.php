<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        if($item->channel_id ==0){
            $item->name = '官方';
            $item->number = '-';
            $item->install = round($item->install/100);
            $item->install_real = round($item->install_real/100);
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
                'field' => 'at_time',
                'type' => 'date',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '时间范围',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    public function handleResultModel($model): array
    {
        $page = $this->rq->input('page', 1);
        $created_at = $this->rq->input('at_time',null);
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
        if($created_at!==null){
            $dateArr = explode('~',$created_at);
            if(isset($dateArr[0]) && isset($dateArr[1])){
                $startTime = strtotime(trim($dateArr[0]).' 00:00:00');
                $endTime = strtotime(trim($dateArr[1]).' 23:59:59');
                $model = $model->where('at_time','>=',$startTime)->where('at_time','<=',$endTime);
            }
        }
        $result = $model->get();
        $handleLists = [];
//        $channelsModel = DB::connection('origin_mysql')->table('channels');
        //$statisticDayModel = DB::connection('origin_mysql')->table('statistic_day');
        foreach ($result as &$res) {
            $info = DB::connection('origin_mysql')->table('channels')->where('id',$res->channel_id)->first();
            if($info){
                if ($res->channel_id > 0 && $info->type==0) {
                    $unitPrice = $info->unit_price;
                    $res->name = $info->name;
                    $res->number = $info->number;
                    $res->unit_price = $unitPrice;
                    $res->install = (int)round($res->install/100);
                    $res->settlement_amount = round($res->unit_price * $res->install,2);
                    if(isset($handleLists[$res->channel_id])){
                        $handleLists[$res->channel_id.'-'.$res->at_time]->install += $res->install;
                    }else{
                        $handleLists[$res->channel_id.'-'.$res->at_time] = $res;
                    }
                }
            }
        }
        $settlement_amount = 0;
        if(!empty($handleLists)){
            $totalPrice = [];
            foreach ($handleLists as $handleList){
                $totalPrice[] = $handleList->settlement_amount;
            }
            $settlement_amount = array_sum($totalPrice);
        }
        $totalRow = [
            'settlement_amount' => $settlement_amount
        ];
        $total = count($handleLists);
        //获取当前页数据
        $offset = ($page-1)*$pagesize;
        $currentPageData = array_slice($handleLists,$offset,$pagesize);
        return [
            'total' => $total,
            'totalRow' => $totalRow ?? [],
            'result' => $currentPageData
        ];
    }

    //首页共享数据
    public function indexShareData()
    {
        //设置首页数据替换
        $this->setListConfig(['open_width' => '600px', 'open_height' => '700px','tableConfig' => ['totalRow' => true]]);
    }
}