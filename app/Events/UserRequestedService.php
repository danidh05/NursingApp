<?php

namespace App\Events;

use App\Models\Request; // Correct model import
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRequestedService implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Request $request
     */
    public function __construct(Request $request) // Correct type hint
    {
        $this->request = $request;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('admin-channel'); // Broadcasting channel for admins
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'user.requested';
    }

    /**
     * The data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Include detailed information in the notification payload
        return [
            'request_id' => $this->request->id,
            'user_name' => $this->request->user->name ?? 'N/A', // Handle nullable relationships
            'nurse_name' => $this->request->nurse->name ?? 'N/A',
            'service_name' => $this->request->service->name ?? 'N/A',
            'status' => $this->request->status,
            'scheduled_time' => $this->request->scheduled_time,
            'location' => $this->request->location,
        ];
    }
}