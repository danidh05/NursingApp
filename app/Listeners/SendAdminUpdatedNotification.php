<?php

namespace App\Listeners;

use App\Events\AdminUpdatedRequest;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $afterCommit = true;     // ensures DB changes are committed
    public $tries = 3;
    
    public function backoff() 
    { 
        return [10, 60]; 
    }

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(AdminUpdatedRequest $event): void
    {
        $this->notificationService->createNotification(
            $event->request->user,
            'Request Status Updated',
            "Your service request status has been updated to: {$event->status}",
            'info'
        );
    }
}