<?php

namespace App\Listeners;

use App\Events\AdminUpdatedRequest;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param AdminUpdatedRequest $event
     * @return void
     */
    public function handle(AdminUpdatedRequest $event)
    {
        \Log::info('SendAdminUpdatedNotification listener triggered for request ID: ' . $event->request->id);

        // Ensure the user is loaded
        $event->request->load('user');

        // Log the user object to confirm it's available
        \Log::info('User associated with request: ', ['user' => $event->request->user]);

        // Check if the request has a valid user assigned
        if (is_null($event->request->user)) {
            \Log::error('No user assigned to request ID: ' . $event->request->id);
            return; // Exit the listener if no user is assigned
        }

        // Define the notification details
        $title = 'Service Request Updated';
        $message = 'Your service request has been updated by an admin.';

        // Send the notification to the user
        $this->notificationService->sendNotification([$event->request->user->id], $title, $message);

        \Log::info('Notification sent successfully for request ID: ' . $event->request->id);
    }
}