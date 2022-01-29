<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use App\Models\ChannelDayStatistic;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;

class TotalMonthController extends BaseCurlController
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
                'field' => 'type',
                'minWidth' => 100,
                'title' => '渠道类型',
                'align' => 'center'
            ],
            [
                'field' => 'channel_name',
                'minWidth' => 100,
                'title' => '渠道名称',
                'align' => 'center'
            ],
            [
                'field' => 'access',
                'minWidth' => 100,
                'title' => '访问量',
                'align' => 'center'
            ],
            [
                'field' => 'hits',
                'minWidth' => 80,
                'title' => '点击量',
//                'hide' => true,
                'align' => 'center',
            ],
            [
                'field' => 'install_real',
                'minWidth' => 80,
                'title' => '安装量',
                'align' => 'center',
            ],
            [
                'field' => 'active_users',
                'minWidth' => 80,
                'title' => '激活人数(有过观景记录的人)',
                'align' => 'center',
            ],
            [
                'field' => 'total_orders',
                'minWidth' => 80,
                'title' => '充值订单数',
                'align' => 'center',
            ],
            [
                'field' => 'total_amount',
                'minWidth' => 80,
                'title' => '充值总金额',
                'align' => 'center',
            ],
        ];
    }

    public function setListOutputItemExtend($item)
    {
        $item->level = $item->pid > 0 ? '二级' : '一级';
        if($item->channel_id ==0){
            $item->install = round($item->install/100);
            $item->install_real = round($item->install_real/100);
            $item->unit_price = '-';
            $item->settlement_amount = '-';
        }
        $item->type = '包月';
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
                'field' => 'query_channel_id_tree',
                'type' => 'select',
                'name' => '顶级渠道',
                'default' => '',
                'data' => $this->getTopChannels(1)
            ],
            [
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '所有渠道',
                'default' => '',
                'data' => $this->getAllChannels(1)
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
        $pagesize = $this->rq->input('limit', 30);
        $order_by_name = $this->orderByName();
        $order_by_type = $this->orderByType();
        $model = $this->orderBy($model, $order_by_name, $order_by_type);
        $result = $model->where('channel_type',1)->get();
        $handleLists = [];
//        $channelsModel = DB::connection('origin_mysql')->table('channels');
        //$statisticDayModel = DB::connection('origin_mysql')->table('statistic_day');
        foreach ($result as &$res) {
            $info = DB::connection('origin_mysql')->table('channels')->where('id',$res->channel_id)->first();
            if($info){
                if ($res->channel_id > 0 && $info->type==0) {
                    $unitPrice = $info->unit_price;
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