<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // or ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;           // broadcast after DB commit
    public string $broadcastQueue = 'broadcasts'; // optional: pin to a queue

    public function __construct(public int $threadId, public array $payload) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('private-chat.'.$this->threadId)];
    }

    public function broadcastAs(): string
    {
        return 'message.created'; // or 'chat.message.created.v1'
    }

    public function broadcastWith(): array
    {
        // payload should already be minimal & signed by the service
        return $this->payload;
    }

    public function broadcastWhen(): bool
    {
        return (bool) config('chat.enabled');
    }
}