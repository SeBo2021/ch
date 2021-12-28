<?php

namespace App\Providers;

use App\ExtendClass\Plugin;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapChannelRoutes();

        $this->mapApiRoutes();

        $this->mapWebRoutes();
        //添加路由admin.php
        $this->mapAdminRoutes();

        //如果关闭插件则不加载
        if (env('OPEN_PLUGIN', 1)) {
            //插件路由
            $this->mapPluginRoutes();
        }

    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace . '\Admin')
            ->group(base_path('routes/admin.php'));
    }

    /**
     * 插件路由实现
     * @return bool
     */
    protected function mapPluginRoutes()
    {
        /**
         * 加载插件路由和帮助方法
         */
        Plugin::loadPluginRouteHelperConfig();

    }


    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function mapChannelRoutes()
    {
        Route::prefix('channel')
            ->middleware('channel')
            ->namespace($this->namespace)
            ->group(base_path('routes/channel.php'));
    }
}
