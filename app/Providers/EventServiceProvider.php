<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\UserRequestedService;
use App\Events\AdminUpdatedRequest;
use App\Events\CustomNotificationSent;
use App\Events\UserBirthday;
use App\Listeners\SendUserRequestedNotification;
use App\Listeners\SendAdminUpdatedNotification;
use App\Listeners\SendCustomNotification;
use App\Listeners\SendBirthdayNotification;

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
        CustomNotificationSent::class => [
            SendCustomNotification::class,
        ],
        UserBirthday::class => [
            SendBirthdayNotification::class,
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