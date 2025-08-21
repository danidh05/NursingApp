<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Services\ChatStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseChatAndPurgeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $threadId)
    {
    }

    public function backoff(): array
    {
        return [5, 30, 60, 120, 300];
    }

    public function handle(ChatStorageService $storage): void
    {
        $thread = ChatThread::query()->find($this->threadId);
        if (!$thread) {
            return;
        }

        $mediaPaths = ChatMessage::query()
            ->where('thread_id', $thread->id)
            ->whereNotNull('media_path')
            ->pluck('media_path')
            ->all();

        $storage->deletePrefix('chats/' . $thread->id . '/');

        $shouldRedact = (bool) config('chat.redact_messages');
        if ($shouldRedact) {
            ChatMessage::query()->where('thread_id', $thread->id)->update([
                'text' => null,
                'media_path' => null,
                'latitude' => null,
                'longitude' => null,
            ]);
        } else {
            // No-op, retain history but media may be removed by storage lifecycle rules
        }

        Log::info('chat cleanup executed', [
            'chat' => true,
            'threadId' => $thread->id,
            'requestId' => $thread->request_id,
            'redacted' => $shouldRedact,
            'media_count' => count($mediaPaths),
        ]);
    }
}