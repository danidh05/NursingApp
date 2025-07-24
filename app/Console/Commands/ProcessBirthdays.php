<?php

namespace App\Console\Commands;

use App\Services\BirthdayService;
use Illuminate\Console\Command;

class ProcessBirthdays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthdays:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily birthday celebrations - send notifications and create popups';

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
        $this->info('Processing birthday celebrations for today...');

        try {
            // Get today's birthday users
            $birthdayUsers = $this->birthdayService->getTodaysBirthdayUsers();

            if ($birthdayUsers->isEmpty()) {
                $this->info('No birthdays today.');
                return 0;
            }

            $this->info("Found {$birthdayUsers->count()} birthday(s) today:");

            foreach ($birthdayUsers as $user) {
                $this->line("- {$user->name} ({$user->email})");
            }

            // Process birthday celebrations
            $this->birthdayService->processBirthdayCelebrations();

            $this->info('✅ Birthday celebrations processed successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error processing birthdays: ' . $e->getMessage());
            return 1;
        }
    }
}
 