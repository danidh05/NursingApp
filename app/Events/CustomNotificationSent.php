<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CustomNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $admin;
    public $title;
    public $message;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $admin
     * @param string $title
     * @param string $message
     */
    public function __construct(User $user, User $admin, string $title, string $message)
    {
        $this->user = $user;
        $this->admin = $admin;
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id), // User-specific channel
            new PrivateChannel('admin.notifications') // Admin channel for admin dashboard
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'custom.notification.sent';
    }

    /**
     * The data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => 'custom',
            'sent_by_admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
        ];
    }
} 