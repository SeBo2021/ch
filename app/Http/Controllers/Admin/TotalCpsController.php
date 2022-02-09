<?php

namespace App\Http\Controllers\Admin;

use App\Models\ChannelDayStatistic;
use App\Models\Users;
use App\TraitClass\ChannelTrait;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class TotalCpsController extends BaseCurlController
{
    use ChannelTrait;
    //设置页面的名称
    public $pageName = 'CPS总计';

    public function setModel(): ChannelDayStatistic
    {
        return $this->model = new ChannelDayStatistic();
    }

    /*public function defaultHandleBtnAddTpl($shareData): array
    {
        return [];
    }*/

    public function indexCols(): array
    {
        return [
            [
                'type' => 'checkbox',
                'totalRowText' => '合计',
            ],
            /*[
                'field' => 'channel_id',
                'width' => 80,
                'title' => '渠道ID',
                'align' => 'center',
            ],*/
            [
                'field' => 'cps',
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
            /*[
                'field' => 'install',
                'minWidth' => 80,
                'title' => '扣量后安装量',
                'align' => 'center',
            ],*/
            [
                'field' => 'active_users',
                'minWidth' => 80,
                'title' => '活跃人数',
                'align' => 'center',
            ],
            [
                'field' => 'active_views',
                'minWidth' => 80,
                'title' => '激活人数(有过观景记录的人)',
                'align' => 'center',
            ],
            [
                'field' => 'install',
                'minWidth' => 80,
                'title' => '扣量后安装量',
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
        $item->cps = 'CPS';
        $item->share_amount = number_format($item->share_amount, 2, '.', '');
        $item->share_ratio = $item->share_ratio . '%';
        //$item->install = '≈'.round($item->install/100);
        return $item;
    }

    public function setOutputSearchFormTpl($shareData)
    {
        $data = [
            [
                'field' => 'query_channel_id_tree',
                'type' => 'select',
                'name' => '顶级渠道',
                'default' => '',
                'data' => $this->getTopChannels(2)
            ],
            [
                'field' => 'query_channel_id',
                'type' => 'select',
                'name' => '所有渠道',
                'default' => '',
                'data' => $this->getAllChannels(2)
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

    public function setOutputHandleBtnTpl($shareData){
        //赋值到ui数组里面必须是`btn`的key值
        $this->uiBlade['btn'] = [];
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
        $model = $model->where('channel_type',2)->where('channel_status',1);
        $result = $model->select('id','channel_id','channel_name','channel_promotion_code','channel_code','channel_pid','channel_type','share_ratio','unit_price',
            DB::raw($fields))
            ->groupBy('channel_id')->orderBy('channel_id','desc')->get();
        $lists = [];
        $installReal = [];
        $install = [];
        $access = [];
        $hits = [];
        $active_users = [];
        $total_orders = [];
        $total_amount = [];
        $share_amount = [];
        $orders = [];
        $total_recharge_amount = [];
        //激活观影人数
        $activeViews = $this->getActiveViews($date_at);
        //$getInstall =
        foreach ($result as $res){
            $res->active_views = $activeViews[$res->channel_id] ?? 0;
            $lists[$res->channel_id] = $res;
            $installReal[] = $res->install_real;
            //$installVal = (int)round($res->install/100);
            $install[] = $res->install;
            $access[] = $res->access;
            $hits[] = $res->hits;
            $active_users[] = $res->active_users;
            $total_orders[] = $res->total_orders;
            $total_amount[] = $res->total_amount;
            $share_amount[] = $res->share_amount;
            $orders[] = $res->orders;
            $total_recharge_amount[] = $res->total_recharge_amount;
        }

        $offset = ($page-1)*$pagesize;
        $currentPageData = array_slice($lists,$offset,$pagesize);

        $total = count($lists);
        $installReal = array_sum($installReal);
        //$install = (int)round(array_sum($install)/100);
        $install = array_sum($install);
        $hits = array_sum($hits);
        $access = array_sum($access);
        $active_users = array_sum($active_users);
        $total_orders = array_sum($total_orders);
        $total_amount = array_sum($total_amount);
        $share_amount = array_sum($share_amount);
        $orders = array_sum($orders);
        $total_recharge_amount = array_sum($total_recharge_amount);
        $totalRow = [
            'install_real' => $installReal>0 ? $installReal :'0',
            'install' => $install>0 ? '≈'.$install :'0',
            'hits' => $hits>0 ? $hits :'0',
            'access' => $access>0 ? $access :'0',
            'active_users' => $active_users>0 ? $active_users :'0',
            'total_orders' => $total_orders>0 ? $total_orders :'0',
            'total_amount' => $total_amount>0 ? $total_amount :'0',
            'share_amount' => $share_amount>0 ? $share_amount :'0',
            'orders' => $orders>0 ? $orders :'0',
            'total_recharge_amount' => $total_recharge_amount>0 ? $total_recharge_amount :'0',
        ];
        return [
            'total' => $total,
            'totalRow' => $totalRow ?? [],
            'result' => $currentPageData
        ];
    }

    /*public function insertIntoCpsData()
    {
        $sql = 'insert into channel_day_statistics(
    channel_id,channel_name,channel_promotion_code,channel_code,channel_pid,total_recharge_amount,share_ratio,share_amount,date_at,orders,total_orders) select
    channel_id,name,promotion_code,channel_code,pid,total_recharge_amount,share_ratio,share_amount,date_at,orders,total_orders from channel_cps;';
        $sql = 'replace into channel_day_statistics(
    channel_id,channel_name,channel_promotion_code,channel_code,channel_pid,total_recharge_amount,share_ratio,share_amount,date_at,orders,total_orders) select
    channel_id,name,promotion_code,channel_code,pid,total_recharge_amount,share_ratio,share_amount,date_at,orders,total_orders from channel_cps;';

        $sql = 'replace into channel_day_statistics(channel_id,access,hits,install_real,install,date_at) select channel_id,access,hits,install_real,install,from_unixtime(at_time, '%Y-%m-%d') as date_at from statistic_day_deduction';
        $sql = 'update channel_day_statistics inner join (select channel_id,from_unixtime(at_time, '%Y-%m-%d') as date_at,count(uid) as users from users_day group by channel_id,date_at) u on channel_day_statistics.channel_id=u.channel_id and channel_day_statistics.date_at=u.date_at set active_users=u.users';
    //update channel_day_statistics inner join (select channel_id,count(id) as users,date_format(created_at, '%Y-%m-%d') as date_at from users group by channel_id,date_at) u on channel_day_statistics.channel_id=u.channel_id and channel_day_statistics.date_at=u.date_at set install_real=u.users;
    更新渠道类型
        $sql = 'update channel_day_statistics inner join (select type,id from channels) c on channel_day_statistics.channel_id=c.id set channel_day_statistics.channel_type=c.type';
        $sql = 'update channel_day_statistics inner join (select type,id,pid from channels) c on channel_day_statistics.channel_id=c.id set channel_day_statistics.channel_pid=c.pid';
    }*/

    //首页共享数据
    public function indexShareData()
    {
        //设置首页数据替换
        $this->setListConfig(['open_width' => '600px', 'open_height' => '700px','tableConfig' => ['totalRow' => true]]);
    }

}