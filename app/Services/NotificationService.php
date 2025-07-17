<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function createNotification(User $user, string $title, string $message, string $type = 'info'): Notification
    {
        // Remove any manual transaction management
        return Notification::create([
            'user_id' => $user->id,
            'message' => $message,
            'type' => $type,
        ]);
    }

    public function markAsRead(int $notificationId, User $user): void
    {
        // Remove any manual transaction management
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function getUserNotifications(User $user): array
    {
        // Remove any manual transaction management
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}