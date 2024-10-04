<?php

namespace App\Events;

use App\Models\Request;
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
        $this->request = $request->load('user', 'services'); // Load user and services relationships

        \Log::info('UserRequestedService event created for request ID: ' . $this->request->id);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('admin-channel');
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
        return [
            'request_id' => $this->request->id,
            'nurse_name' => optional($this->request->nurse)->name ?? 'N/A', 
            'service_names' => $this->request->services->pluck('name')->toArray(),
            'status' => $this->request->status,
            'scheduled_time' => $this->request->scheduled_time ? $this->request->scheduled_time->toDateTimeString() : null,
            'ending_time' => $this->request->ending_time ? $this->request->ending_time->toDateTimeString() : null,
            'location' => $this->request->location,
            'nurse_gender' => $this->request->nurse_gender,
            'problem_description' => $this->request->problem_description,
            'full_name' => $this->request->full_name,
            'phone_number' => $this->request->phone_number,
            'user_id' => $this->request->user_id,  // Ensure the user_id is broadcasted
        ];
    }
}