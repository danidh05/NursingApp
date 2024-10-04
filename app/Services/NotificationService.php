<?php

namespace App\Services;

use App\Models\User; // Import the User model
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class NotificationService
{
    // Function to send notification via OneSignal
    public function sendNotification(array $userIds, string $title, string $body)
    {
        \Log::info('NotificationService: sendNotification called', ['user_ids' => $userIds, 'title' => $title, 'body' => $body]);

        // Retrieve the users with the provided IDs
        $users = User::whereIn('id', $userIds)->get();

        // Prepare an array of external IDs from the users
        $externalIds = $users->pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();
        

        $response = Http::withHeaders([
            'Authorization' => 'Basic MzZjZDIwMWItOTU2OS00N2M3LTk1OTEtOTI2N2E4YzU5OTEx', // Your OneSignal API Key
            'Content-Type' => 'application/json',
        ])->post('https://api.onesignal.com/notifications', [
            'app_id' => '788828aa-6b79-4ef4-94a5-4d3d9e67bed1', // Your OneSignal App ID
            'include_external_user_ids' => $externalIds, // Use the user IDs as external IDs
            'target_channel' => 'push',
            'headings' => [
                'en' => $title, // The title of the notification
            ],
            'contents' => [
                'en' => $body, // The body of the notification
            ],
        ]);

        // Handle the response from OneSignal
        if ($response->successful()) {
            \Log::info('Notification sent successfully.', ['response' => $response->json()]);
            return "Notification sent successfully.";
        } else {
            \Log::info('Notification failed ya feshel.', ['response' => $response->json()]);
          //  Log::error('Failed to send notification.', ['response' => $response->json()]);
            return "Failed to send notification: " . $response->body();
        }
    }
}