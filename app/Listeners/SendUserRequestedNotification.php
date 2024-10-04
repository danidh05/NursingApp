<?php

namespace App\Listeners;

use App\Events\UserRequestedService;
use App\Services\NotificationService;
use App\Models\User;  // Import the User model
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendUserRequestedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(UserRequestedService $event)
    {
        \Log::info('SendUserRequestedNotification listener triggered for request ID: ' . $event->request->id);

        // Check if the request has a valid user_id
        if (is_null($event->request->user_id)) {
            \Log::error('No user_id assigned to request ID: ' . $event->request->id);
            return; // Exit the listener if no user_id is assigned
        }

        // Load the user relationship to access user details
        $event->request->load('user');

        \Log::info('User associated with request: ', ['user' => $event->request->user]);

        // Get the user who created the request
        // $requestingUser = $event->request->user;

        // Define the title and message for the notification
        $title = 'New Service Request';
        $message = 'A new service request has been created by ' . $event->request->full_name;

        // Retrieve the admin's user IDs (assuming 'role' is the field to determine admin)
        $adminIds = User::where('role_id', 1)->pluck('id')->toArray();

        if (empty($adminIds)) {
            \Log::error('No admins found to send notification.');
            return;
        }

       // \Log::info('Sending notification from user ID: ' . $requestingUser->id . ' to admins with IDs: ' . implode(',', $adminIds));

        // Send notification to the admins
        $this->notificationService->sendNotification($adminIds, $title, $message);

        \Log::info('Notification sent successfully for request ID: ' . $event->request->id);
    }
}