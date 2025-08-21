<?php

namespace App\Policies;

use App\Models\ChatThread;
use App\Models\User;

class ChatThreadPolicy
{
    /**
     * Optional global override and feature-flag guard.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (optional($user->role)->name === 'admin') {
            return true;
        }
        if (!config('chat.enabled')) {
            return false;
        }
        return null;
    }

    /**
     * Allow viewing/subscribing even if closed.
     */
    public function view(User $user, ChatThread $thread): bool
    {
        return in_array($user->id, array_filter([$thread->client_id, $thread->admin_id]), true);
    }

    /**
     * Allow posting only when open.
     */
    public function post(User $user, ChatThread $thread): bool
    {
        return $thread->status === 'open'
            && in_array($user->id, array_filter([$thread->client_id, $thread->admin_id]), true);
    }

    /**
     * Allow closing even if already closed (service handles idempotency).
     */
    public function close(User $user, ChatThread $thread): bool
    {
        return in_array($user->id, array_filter([$thread->client_id, $thread->admin_id]), true);
    }
}