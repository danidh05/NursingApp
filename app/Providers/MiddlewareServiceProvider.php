<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use App\Http\Middleware\RoleMiddleware;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @param  Router  $router
     * @return void
     */
    public function boot(Router $router)
    {
        // Register the 'role' middleware alias
        $router->aliasMiddleware('role', RoleMiddleware::class);
    }
}