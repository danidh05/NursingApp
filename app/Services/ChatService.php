<?php

namespace App\Services;

use App\Events\Chat\MessageCreated;
use App\Events\Chat\ThreadClosed;
use App\Jobs\CloseChatAndPurgeMediaJob;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class ChatService
{
    public function __construct(private ChatStorageService $storageService)
    {
    }

    protected function ensureChatEnabled(): void
    {
        if (!Config::get('chat.enabled')) {
            abort(501, 'Chat feature is disabled');
        }
    }

    public function openThread(int $requestId, User $actor): ChatThread
    {
        $this->ensureChatEnabled();

        $request = Request::query()->findOrFail($requestId);

        return DB::transaction(function () use ($request, $actor) {
            $thread = ChatThread::query()->where('request_id', $request->id)->first();
            if ($thread) {
                return $thread;
            }

            $isAdmin = optional($actor->role)->name === 'admin';

            $thread = ChatThread::create([
                'request_id' => $request->id,
                'admin_id' => $isAdmin ? $actor->id : null,
                'client_id' => $request->user_id,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            Log::info('chat thread opened', [
                'chat' => true,
                'threadId' => $thread->id,
                'requestId' => $request->id,
                'adminId' => $thread->admin_id,
                'clientId' => $thread->client_id,
            ]);

            return $thread;
        });
    }

    public function postMessage(int $threadId, User $sender, array $payload): ChatMessage
    {
        $thread = ChatThread::query()->findOrFail($threadId);

        // Authorization check is now handled by the policy in the controller
        // if (!\Gate::allows('post', $thread)) {
        //     abort(403, 'Not authorized to post to this thread');
        // }

        // Thread status check is now handled by the policy
        // if ($thread->status !== 'open') {
        //     abort(422, 'Thread is closed');
        // }

        $type = $payload['type'] ?? 'text';
        if (!in_array($type, ['text', 'image', 'location'])) {
            abort(422, 'Invalid message type');
        }

        $data = [
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'type' => $type,
        ];

        if ($type === 'text') {
            $text = trim((string)($payload['text'] ?? ''));
            if ($text === '') {
                abort(422, 'Text is required');
            }
            $data['text'] = $text;
        } elseif ($type === 'location') {
            $lat = $payload['lat'] ?? null;
            $lng = $payload['lng'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng)) {
                abort(422, 'Invalid coordinates');
            }
            $data['latitude'] = (float)$lat;
            $data['longitude'] = (float)$lng;
        } elseif ($type === 'image') {
            $mediaPath = (string)($payload['mediaPath'] ?? '');
            if ($mediaPath === '' || !$this->storageService->validateChatPath($thread->id, $mediaPath)) {
                abort(422, 'Invalid media path for this thread');
            }
            $data['media_path'] = $mediaPath;
        }

        $message = ChatMessage::create($data);

        $signedUrl = null;
        if ($message->type === 'image' && $message->media_path) {
            $ttl = (int)Config::get('chat.signed_url_ttl', 900);
            $signedUrl = $this->storageService->signGetUrl($message->media_path, $ttl);
        }

        MessageCreated::dispatch($thread->id, [
            'id' => $message->id,
            'type' => $message->type,
            'text' => $message->text,
            'lat' => $message->latitude,
            'lng' => $message->longitude,
            'media_url' => $signedUrl,
            'sender_id' => $message->sender_id,
            'created_at' => $message->created_at,
        ]);

        Log::info('chat message created', [
            'chat' => true,
            'threadId' => $thread->id,
            'requestId' => $thread->request_id,
            'messageId' => $message->id,
            'senderId' => $sender->id,
            'type' => $message->type,
        ]);

        return $message;
    }

    public function closeThread(int $threadId, User $actor): ChatThread
    {
        $this->ensureChatEnabled();

        $thread = ChatThread::query()->findOrFail($threadId);

        // Authorization check is now handled by the policy in the controller
        // if (!\Gate::allows('close', $thread)) {
        //     abort(403, 'Not authorized to close this thread');
        // }

        if ($thread->status === 'closed') {
            return $thread;
        }

        $thread->status = 'closed';
        $thread->closed_at = now();
        $thread->save();

        CloseChatAndPurgeMediaJob::dispatch($thread->id)->onQueue('default');

        ThreadClosed::dispatch($thread->id);

        Log::info('chat thread closed', [
            'chat' => true,
            'threadId' => $thread->id,
            'requestId' => $thread->request_id,
        ]);

        return $thread;
    }
}