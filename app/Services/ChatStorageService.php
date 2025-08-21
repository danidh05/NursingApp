<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatStorageService
{
    public function __construct(private FirebaseStorageService $firebase) {}

    public function signGetUrl(string $objectName, int $ttlSeconds): ?string
    {
        if ($this->traversal($objectName)) return null;
        return $this->firebase->signV4GetUrl($objectName, $ttlSeconds);
    }

    public function signPutUrl(string $objectName, string $contentType, int $ttlSeconds): array
    {
        if ($this->traversal($objectName)) return ['url' => null, 'headers' => []];

        $allowed = collect(explode(',', (string) config('chat.allowed_image_mime')))
            ->map(fn($m) => trim($m))->filter()->values();

        if (!$allowed->contains($contentType)) {
            return ['url' => null, 'headers' => []];
        }

        return $this->firebase->signV4PutUrl($objectName, $contentType, $ttlSeconds);
    }

    public function deletePrefix(string $prefix): void
    {
        if ($this->traversal($prefix)) {
            Log::warning('chat cleanup rejected suspicious prefix', ['prefix' => $prefix]);
            return;
        }
        $count = $this->firebase->deleteByPrefix($prefix);
        Log::info('chat cleanup deleted objects', ['prefix' => $prefix, 'count' => $count]);
    }

    public function validateChatPath(int $threadId, string $objectName): bool
    {
        return Str::startsWith($objectName, "chats/{$threadId}/") && !$this->traversal($objectName);
    }

    private function traversal(string $p): bool
    {
        return str_contains($p, '..') || str_starts_with($p, '/') || str_starts_with($p, '\\');
    }
}