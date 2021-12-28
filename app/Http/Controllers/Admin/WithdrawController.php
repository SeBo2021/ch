<?php


namespace App\Http\Controllers\Admin;


use App\Models\Withdraw;

class WithdrawController extends BaseCurlIndexController
{
    public $pageName = "提现记录";

    public function setModel()
    {
        return $this->model = new Withdraw();
    }

    public function IndexCols ()
    {
        $cols = [
            [
                'type' => 'checkbox'
            ],
            [
                'field' => 'id',
                'width' => 80,
                'title' => '编号',
                'sort' => 1,
                'align' => 'center'
            ],
            [
                'field' => 'mid',
                'width' => 100,
                'title' => '会员',
                'align' => 'center'
            ],
            [
                'field' => 'amount',
                'minWidth' => 100,
                'title' => '提现金额',
                'align' => 'center'
            ],
            [
                'field' => 'type',
                'minWidth' => 100,
                'title' => '提现方式',
                'align' => 'center'
            ],
            [
                'field' => 'created_at',
                'minWidth' => 150,
                'title' => '创建时间',
                'align' => 'center'
            ],
            [
                'field' => 'handle',
                'minWidth' => 150,
                'title' => '操作',
                'align' => 'center'
            ]
        ];
        return $cols;
    }


}