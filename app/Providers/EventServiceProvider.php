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
use App\Events\Chat\MessageCreated;
use App\Events\Chat\ThreadClosed;

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
        // Chat events are broadcast-only; listeners optional
        MessageCreated::class => [
        ],
        ThreadClosed::class => [
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