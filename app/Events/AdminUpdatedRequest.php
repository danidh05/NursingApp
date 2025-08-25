<?php

namespace App\Events;

use App\Models\Request; // Correct model import
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdminUpdatedRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;
    public $user;
    public $status;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Request $request
     * @param \App\Models\User $user
     * @param string $status
     */
    public function __construct(Request $request, User $user, string $status)
    {
        $this->request = $request->load('user');
        $this->user = $user;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->user_id), // User-specific channel
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
        return 'admin.updated.request';
    }

    /**
     * The data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->request->id,
            'user_id' => $this->request->user_id,
            'status' => $this->status,
            'updated_by_admin_id' => $this->user->id,
            'admin_name' => $this->user->name,
            'updated_at' => $this->request->updated_at->toISOString(),
        ];
    }
}