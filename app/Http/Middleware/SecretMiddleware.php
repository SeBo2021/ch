<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class SecretMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $content = $response->getContent();
        if ($content) {
            # 对 content 进行加密处理
            $content = Crypt::encryptString($content);
            $response->setContent($content);
        }
        return $response;
    }

}
