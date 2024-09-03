<?php

namespace App\Events;

use App\Models\Request; // Correct model import
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdminUpdatedRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Request $request
     */
    public function __construct(Request $request) // Use the correct type hint
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
        return new Channel('user-channel.' . $this->request->user_id); // Ensure the user_id is accessible
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'admin.updated'; // Event alias name
    }

    /**
     * The data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'request_id' => $this->request->id,
            'status' => $this->request->status,
            'nurse_name' => $this->request->nurse->name ?? 'N/A', // Handle nullable relationships
            'service_name' => $this->request->service->name ?? 'N/A',
            'scheduled_time' => $this->request->scheduled_time,
            'location' => $this->request->location,
            'updated_at' => $this->request->updated_at->toDateTimeString(),
        ];
    }
}