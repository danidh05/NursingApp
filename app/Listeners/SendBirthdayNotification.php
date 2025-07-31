<?php

namespace App\Listeners;

use App\Events\UserBirthday;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBirthdayNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(UserBirthday $event): void
    {
        $this->notificationService->sendBirthdayNotification($event->user);
    }
} 