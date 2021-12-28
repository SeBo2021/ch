<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * 将要排除的 URL 添加到 $except 属性数组
     * @var array
     */
    protected $except = [
        //app客户端上传功能不作此类较验
//        'http://192.168.1.249/api/upload',
    ];
}
