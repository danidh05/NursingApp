<?php

namespace App\Services;

use App\Models\User;
use App\Models\Popup;
use App\Services\PopupService;
use App\Services\NotificationService;
use App\Events\UserBirthday;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class BirthdayService
{
    public function __construct(
        private PopupService $popupService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get all users having birthday today
     */
    public function getTodaysBirthdayUsers(): Collection
    {
        $today = now();
        $driver = \DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite compatible date functions
            return User::whereRaw("strftime('%m', birth_date) = ? AND strftime('%d', birth_date) = ?", [
                $today->format('m'),
                $today->format('d')
            ])->get();
        } else {
            // MySQL compatible date functions
            return User::whereRaw('MONTH(birth_date) = ? AND DAY(birth_date) = ?', [
                $today->month,
                $today->day
            ])->get();
        }
    }

    /**
     * Create or get birthday popup for a user
     */
    private function getBirthdayPopup(User $user): Popup
    {
        // Try to find an existing birthday popup for this specific user today
        $popup = Popup::where('type', Popup::TYPE_BIRTHDAY)
            ->where('is_active', true)
            ->where('user_id', $user->id)
            ->whereDate('start_date', now()->toDateString())
            ->first();

        // If no birthday popup exists, create one
        if (!$popup) {
            $popup = $this->popupService->createPopup([
                'image' => 'https://via.placeholder.com/800x600/FFD700/FFFFFF?text=🎂+Happy+Birthday!',
                'title' => '🎉 Happy Birthday!',
                'content' => "Happy Birthday {$user->name}! 🎂\nWe hope you have a wonderful day filled with joy and happiness.",
                'type' => Popup::TYPE_BIRTHDAY,
                'is_active' => true,
                'user_id' => $user->id,
                'start_date' => now()->startOfDay(),
                'end_date' => now()->endOfDay(),
            ]);
        }

        return $popup;
    }

    /**
     * Send birthday notification to a user
     */
    private function sendBirthdayNotification(User $user): void
    {
        // Dispatch the birthday event - the listener will handle the notification
        event(new UserBirthday($user));
    }

    /**
     * Process birthday celebrations for today
     */
    public function processBirthdayCelebrations(): void
    {
        $birthdayUsers = $this->getTodaysBirthdayUsers();

        foreach ($birthdayUsers as $user) {
            // Create/get birthday popup
            $popup = $this->getBirthdayPopup($user);

            // Send notification
            $this->sendBirthdayNotification($user);
        }
    }
} 