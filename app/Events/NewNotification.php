<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $userId;

    public function __construct($message, $userId = null)
    {
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        // Use user-specific channel if userId is provided, otherwise use general channel
        if ($this->userId) {
            return [new PrivateChannel('user.' . $this->userId)];
        }
        return [new PrivateChannel('notifications')]; // General channel for system notifications
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return $this->message;
    }
}