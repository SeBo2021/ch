<?php
/**
 * 支付中心接口契约
 */

namespace App\Services;

use Illuminate\Http\Request;

interface Pay
{
    /**
     * 支付动作
     * @param Request $request
     * @return mixed
     */
    public function pay(Request $request): mixed;

    /**
     * 支付回调
     * @param Request $request
     * @return mixed
     */
    public function callback(Request $request): mixed;

    /**
     * 支付方式查询
     * @param Request $request
     * @return mixed
     */
    public function method(Request $request): mixed;
}