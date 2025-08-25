<?php

namespace App\Listeners;

use App\Events\CustomNotificationSent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCustomNotification implements ShouldQueue
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

    public function handle(CustomNotificationSent $event): void
    {
        $this->notificationService->createNotification(
            $event->user,
            $event->title,
            $event->message,
            'custom',
            $event->admin->id
        );
    }
} 