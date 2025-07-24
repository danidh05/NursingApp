<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BirthdayService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestBirthdayFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:test {email} {--reset : Reset user birth_date to original}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test birthday feature by temporarily setting user birth_date to today';

    public function __construct(
        private BirthdayService $birthdayService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $reset = $this->option('reset');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        if ($reset) {
            // Reset to original birth_date (you might want to store this)
            $user->update(['birth_date' => '1990-01-01']);
            $this->info("Reset {$user->name}'s birth_date to original.");
            return 0;
        }

        // Store original birth_date for reset
        $originalBirthDate = $user->birth_date;

        // Set birth_date to today
        $today = now()->format('Y-m-d');
        $user->update(['birth_date' => $today]);

        $this->info("Set {$user->name}'s birth_date to today ({$today})");

        // Process birthdays
        $this->info("Processing birthdays...");
        $this->birthdayService->processBirthdayCelebrations();

        $this->info("✅ Birthday feature tested successfully!");
        $this->info("📱 Test these endpoints:");
        $this->info("   GET /api/popups (with user token)");
        $this->info("   GET /api/notifications (with user token)");
        $this->info("   GET /api/admin/popups (with admin token)");
        
        $this->warn("⚠️  Don't forget to reset: php artisan birthday:test {$email} --reset");

        return 0;
    }
} 