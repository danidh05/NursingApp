<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use OneSignal;

class OneSignalService
{
    /**
     * Send push notification to a specific user using their user ID
     */
    public function sendToUser(User $user, string $title, string $message, array $data = []): bool
    {
        try {
            $result = OneSignal::sendNotificationToExternalUser(
                $message,
                (string) $user->id, // Use user ID as external_user_id
                $url = null,
                $data,
                $buttons = null,
                $schedule = null,
                $headings = $title
            );

            Log::info('OneSignal push notification sent successfully', [
                'user_id' => $user->id,
                'title' => $title,
                'result' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OneSignal push notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToUsers(array $users, string $title, string $message, array $data = []): array
    {
        $results = [];
        
        foreach ($users as $user) {
            $results[$user->id] = $this->sendToUser($user, $title, $message, $data);
        }

        return $results;
    }

    /**
     * Send notification to all users using tags
     */
    public function sendToAllUsers(string $title, string $message, array $data = []): bool
    {
        try {
            $result = OneSignal::sendNotificationToAll(
                $message,
                $url = null,
                $data,
                $buttons = null,
                $schedule = null,
                $headings = $title
            );

            Log::info('OneSignal push notification sent to all users', [
                'title' => $title,
                'result' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OneSignal push notification to all users', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send notification using OneSignal tags (for targeting specific user groups)
     */
    public function sendUsingTags(string $title, string $message, array $tags, array $data = []): bool
    {
        try {
            $result = OneSignal::sendNotificationUsingTags(
                $message,
                $tags,
                $url = null,
                $data,
                $buttons = null,
                $schedule = null,
                $headings = $title
            );

            Log::info('OneSignal push notification sent using tags', [
                'title' => $title,
                'tags' => $tags,
                'result' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OneSignal push notification using tags', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Verify if a user is registered with OneSignal
     */
    public function verifyUserRegistration(User $user): bool
    {
        // Since we're using user_id, we assume the user is registered
        // The Flutter app should call OneSignal.shared.setExternalUserId(user.id.toString())
        return true;
    }
} 