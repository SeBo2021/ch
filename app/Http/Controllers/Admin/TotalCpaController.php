<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpa;
use App\Models\ChannelDayStatistic;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;

class TotalCpaController extends BaseCurlController
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
                'field' => 'cpa',
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
                'field' => 'principal',
                'minWidth' => 100,
                'title' => '负责人',
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
                'title' => '活跃人数',
                'align' => 'center',
            ],
            [
                'field' => 'active_view_users',
                'minWidth' => 80,
                'title' => '激活人数(有过观影记录的人)',
                'align' => 'center',
            ],
            [
                'field' => 'install',
                'minWidth' => 80,
                'title' => '扣量后安装量',
                'align' => 'center',
            ],
            [
                'field' => 'unit_price',
                'minWidth' => 80,
                'title' => '单价(¥)',
                'align' => 'center',
            ],
            [
                'field' => 'share_amount',
                'minWidth' => 80,
                'title' => '结算金额(¥)',
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
        $install = (int)$item->install;
        $item->share_amount = round($item->unit_price * $install,2);
        $item->level = $item->pid > 0 ? '二级' : '一级';
        if($item->channel_id ==0){
            $item->channel_name = '官方';
            $item->channel_code = '-';
            $item->unit_price = '-';
            $item->share_amount = '-';
        }

        $item->at_time =  date('Y-m-d',$item->at_time);
        $item->cpa = 'CPA';
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
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        $date_at = $this->rq->input('query_date_at', null);

        $fields = 'SUM(access) as access,
                SUM(hits) as hits,
                SUM(install_real) as install_real,
                SUM(active_users) as active_users,
                SUM(active_view_users) as active_view_users,
                SUM(total_orders) as total_orders,
                SUM(total_amount) as total_amount,
                SUM(share_amount) as share_amount,
                SUM(orders) as orders,
                SUM(total_recharge_amount) as total_recharge_amount,
                SUM(ROUND(install/100)) as install';
        if($date_at===null){
            $defaultDate = date('Y-m-d',strtotime('-3 month'));
            $model = $model->where('date_at','>=',$defaultDate);
        }
        $model = $model->where('channel_type',0)->where('channel_status',1)->where('channel_id','>',0)->select('id','channel_id','principal','channel_name','channel_promotion_code','channel_code','channel_pid','channel_type','share_ratio','unit_price',DB::raw($fields))->groupBy('channel_id');
        $result = $model->orderBy('channel_id','desc')->get();
        $lists = [];
        $install = [];
        $install_real = [];
        $access = [];
        $hits = [];
        $active_users = [];
        $total_orders = [];
        $total_amount = [];
        $share_amount = [];
        $orders = [];
        $total_recharge_amount = [];
        //激活观影人数
        //$activeViews = $this->getActiveViews($date_at);
        foreach ($result as $res){
            //$res->active_views = $activeViews[$res->channel_id] ?? 0;
            $res->active_views = $res->active_view_users;
            $lists[$res->channel_id] = $res;
            /*$installVal = (int)round($res->install/100);
            $install[] = $installVal;*/
            $installVal = (int)$res->install;
            $install[] = $installVal;
            $install_real[] = $res->install_real;
            $access[] = $res->access;
            $hits[] = $res->hits;
            $active_users[] = $res->active_users;
            $total_orders[] = $res->total_orders;
            $total_amount[] = $res->total_amount;
            $share_amount[] = round($res->unit_price * $installVal,2);
            $orders[] = $res->orders;
            $total_recharge_amount[] = $res->total_recharge_amount;
        }

        $offset = ($page-1)*$pagesize;
        $currentPageData = array_slice($lists,$offset,$pagesize);

        $total = count($lists);
        $install = array_sum($install);
        $install_real = array_sum($install_real);
        $hits = array_sum($hits);
        $access = array_sum($access);
        $active_users = array_sum($active_users);
        $total_orders = array_sum($total_orders);
        $total_amount = array_sum($total_amount);
        $share_amount = array_sum($share_amount);
        $orders = array_sum($orders);
        $total_recharge_amount = array_sum($total_recharge_amount);
        $totalRow = [
            'install' => $install>0 ? $install :'0',
            'install_real' => $install_real>0 ? $install_real :'0',
            'hits' => $hits>0 ? $hits :'0',
            'access' => $access>0 ? $access :'0',
            'active_users' => $active_users>0 ? $active_users :'0',
            'total_orders' => $total_orders>0 ? $total_orders :'0',
            'total_amount' => $total_amount>0 ? $total_amount :'0',
            'share_amount' => $share_amount>0 ? round($share_amount,2) :'0',
            'orders' => $orders>0 ? $orders :'0',
            'total_recharge_amount' => $total_recharge_amount>0 ? $total_recharge_amount :'0',
        ];
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