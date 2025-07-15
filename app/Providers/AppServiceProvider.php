<?php

namespace App\Providers;

use App\Repositories\Interfaces\IRequestRepository;
use App\Repositories\RequestRepository;
use App\Services\Interfaces\IRequestService;
use App\Services\RequestService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Request Repository
        $this->app->bind(IRequestRepository::class, RequestRepository::class);

        // Register Request Service
        $this->app->bind(IRequestService::class, RequestService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
