<?php

namespace App\Http;

use App\ExtendClass\Plugin;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\CheckPermission;
//use App\Http\Middleware\InstallMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
//        \App\Http\Middleware\EnableCrossRequestMiddleware::class
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class, //一定要放在最后
        ],

        'api' => [
            'throttle:30,1',
//            'throttle:60,1', 一分钟60次
            //'auth:api', 路由添加
            //'secret',
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ],

        'channel' => [
            'throttle:30,1',
            \App\Http\Middleware\EnableCrossRequestMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ]
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
        'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
        'client' => CheckClientCredentials::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
//        'install' => InstallMiddleware::class,
        'admin_auth' => AdminAuthMiddleware::class,
        'permission' => CheckPermission::class,
        'secret' => \App\Http\Middleware\SecretMiddleware::class,
    ];

    /**
     * 重写此方法，加载插件的中间件
     */
    protected function syncMiddlewareToRouter()
    {
        $this->router->middlewarePriority = $this->middlewarePriority;
        //如果关闭了插件则不加载
        if (env('OPEN_PLUGIN', 1)) {
            //加载插件中间件
            $config = Plugin::loadPluginMiddleware($this->middlewareGroups, $this->routeMiddleware);
            $this->middlewareGroups = $config['group'];
            $this->routeMiddleware = $config['middleware'];

        }

        foreach ($this->middlewareGroups as $key => $middleware) {
            $this->router->middlewareGroup($key, $middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $this->router->aliasMiddleware($key, $middleware);
        }
    }
}
