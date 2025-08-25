<?php

namespace App\Events;

use App\Models\Request;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRequestedService implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;
    public $user;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Request $request
     * @param \App\Models\User $user
     */
    public function __construct(Request $request, User $user)
    {
        $this->request = $request->load('user', 'services');
        $this->user = $user;

        \Log::info('UserRequestedService event created for request ID: ' . $this->request->id);
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
        return 'user.requested.service';
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