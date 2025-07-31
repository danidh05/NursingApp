<?php

namespace App\Listeners;

use App\Events\AdminUpdatedRequest;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(AdminUpdatedRequest $event): void
    {
        $this->notificationService->createNotification(
            $event->user,
            'Request Status Updated',
            "Your request status has been updated to: {$event->status}",
            'info'
        );
    }
}