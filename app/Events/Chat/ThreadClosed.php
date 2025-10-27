<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // or ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ThreadClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $threadId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->threadId)];
    }

    public function broadcastAs(): string
    {
        return 'thread.closed';
    }

    public function broadcastWith(): array
    {
        return ['thread_id' => $this->threadId];
    }

    public function broadcastWhen(): bool
    {
        return (bool) config('chat.enabled');
    }
}