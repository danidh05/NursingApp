<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private OneSignalService $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        $this->oneSignalService = $oneSignalService;
    }

    public function createNotification(User $user, string $title, string $message, string $type = 'info', ?int $sentByAdminId = null): Notification
    {
        // Create notification in database first
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'sent_by_admin_id' => $sentByAdminId,
        ]);

        // Send OneSignal push notification using user ID (handle failures gracefully)
        try {
            $this->oneSignalService->sendToUser(
                $user,
                $title,
                $message,
                [
                    'notification_id' => $notification->id,
                    'notification_type' => $type,
                    'title' => $title
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send OneSignal notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't re-throw - notification was created successfully in database
        }

        // Broadcast via Pusher for real-time updates when app is open
        try {
            broadcast(new NewNotification($notification->toArray(), $user->id))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast notification', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return $notification;
    }

    /**
     * Send a custom notification from admin to user
     */
    public function sendCustomNotification(User $user, string $title, string $message, User $admin): Notification
    {
        return $this->createNotification($user, $title, $message, 'custom', $admin->id);
    }

    /**
     * Send birthday notification to user
     */
    public function sendBirthdayNotification(User $user): Notification
    {
        return $this->createNotification(
            $user,
            '🎉 Happy Birthday!',
            'Wishing you a wonderful birthday filled with joy and happiness!',
            'birthday'
        );
    }

    public function markAsRead(int $notificationId, User $user): void
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function getUserNotifications(User $user): array
    {
        return $user->notifications()
            ->with('sentByAdmin:id,name,email') // Include admin info for custom notifications
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get custom notifications sent by a specific admin
     */
    public function getCustomNotificationsSentByAdmin(User $admin): array
    {
        return Notification::where('sent_by_admin_id', $admin->id)
            ->where('type', 'custom')
            ->with('user:id,name,email') // Include user info
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }


}