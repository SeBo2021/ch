<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelCpsTotal;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class TotalCpsController extends BaseCurlController
{
    //设置页面的名称
    public $pageName = 'CPS总计';

    public function setModel()
    {
        return $this->model = new ChannelCpsTotal();
    }

    /*public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }*/

    public function indexCols(): array
    {
        return [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'channel_id',
                'width' => 80,
                'title' => '渠道ID',
                'align' => 'center',
            ],
            [
                'field' => 'cps',
                'minWidth' => 100,
                'title' => '渠道类型',
                'align' => 'center'
            ],
            [
                'field' => 'name',
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
                'field' => 'install',
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
            [
                'field' => 'orders',
                'minWidth' => 80,
                'title' => '扣量后订单数',
                'align' => 'center',
            ],
            [
                'field' => 'total_recharge_amount',
                'minWidth' => 80,
                'title' => '扣量后充值总金额',
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
                'title' => '结算金额',
                'align' => 'center',
            ],
            /*[
                'field' => 'date_at',
                'minWidth' => 150,
                'title' => '统计日期',
                'align' => 'center'
            ],*/
        ];
    }

    public function setListOutputItemExtend($item)
    {
        $item->access = 0;
        $item->cps = 'CPS';
        $item->hits = 0;
        $item->install = 0;
        $item->active_users = 0;
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
            /*[
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '渠道',
                'default' => '',
                'data' => $this->getCpsChannels()
            ],*/
            [
                'field' => 'query_date_at',
                'type' => 'date',
                'attr' => 'data-range=~',//需要特殊分割
                'name' => '选择日期',
            ]
        ];
        //赋值到ui数组里面必须是`search`的key值
        $this->uiBlade['search'] = $data;
    }

    public function setOutputHandleBtnTpl($shareData){
        //赋值到ui数组里面必须是`btn`的key值
        $this->uiBlade['btn'] = [];
    }

    public function handleResultModel($model): array
    {
        $page = $this->rq->input('page', 1);
        $pagesize = $this->rq->input('limit', 30);
        //channel_cps
        /*$fields1 = 'SUM(access) as total_access,
                SUM(hits) as total_hits,
                SUM(install) as total_install,
                SUM(register) as total_register,
                SUM(keep_day_users) as total_keep_day_users,
                SUM(keep_week_users) as total_keep_week_users,
                SUM(keep_month_users) as total_keep_month_users,
                SUM(keep_day_rate) as total_keep_day_rate,
                SUM(keep_week_rate) as total_keep_week_rate,
                SUM(keep_month_rate) as total_keep_month_rate';*/
        $fields1 = 'SUM(install) as total_install,
                SUM(orders) as orders,
                SUM(total_recharge_amount) as total_recharge_amount,
                SUM(total_amount) as total_amount,
                SUM(share_amount) as share_amount,
                SUM(total_orders) as total_orders';
        $cpsBuild = $model->select('id','pid','name','channel_id','share_ratio',DB::raw($fields1));
        $result_cps = $cpsBuild->groupBy('channel_id')->orderBy('channel_id','desc')->get();
        $list = [];
        foreach ($result_cps as $res){
            $list[$res->channel_id] = $res;
        }
        //统计访问量、点击量、安装量 todo

        //激活人数(有过观景记录的人)
        $activeUsersBuild = DB::connection('origin_mysql')->table('users_day');
        $result_active_users = $activeUsersBuild->select('pid','channel_id','at_time',DB::raw('count(uid) as users'))->groupBy('channel_id')->get();
        foreach ($result_active_users as $result_active_user)
        {
            $list[$result_active_user->channel_id]->active_users = $result_active_user->active_users;
        }

        $total = count($list);
        //获取当前页数据
        $offset = ($page-1)*$pagesize;
        $currentPageData = array_slice($list,$offset,$pagesize);
        return [
            'total' => $total,
            'result' => $currentPageData
        ];
    }

}