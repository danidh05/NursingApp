<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Listeners\SendUserRequestedNotification;
use App\Listeners\SendAdminUpdatedNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        UserRequestedService::class => [
            SendUserRequestedNotification::class,
        ],
        AdminUpdatedRequest::class => [
            SendAdminUpdatedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}