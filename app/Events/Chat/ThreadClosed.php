<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // or ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ThreadClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;
    public string $broadcastQueue = 'broadcasts';

    public function __construct(public int $threadId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->threadId)];
    }

    public function broadcastAs(): string
    {
        return 'thread.closed'; // or 'chat.thread.closed.v1'
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