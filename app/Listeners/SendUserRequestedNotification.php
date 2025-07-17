<?php

namespace App\Listeners;

use App\Events\UserRequestedService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendUserRequestedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(UserRequestedService $event): void
    {
        // Remove any manual transaction management
        $this->notificationService->createNotification(
            $event->user,
            'Service Request Submitted',
            'Your service request has been submitted successfully.',
            'success'
        );
    }
}