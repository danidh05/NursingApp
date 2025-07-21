<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TestDataSeeder;

class SeedTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:test-data {--fresh : Run fresh migration before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with comprehensive test data for Postman testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Preparing to seed comprehensive test data...');
        
        if ($this->option('fresh')) {
            $this->info('🔄 Running fresh migration...');
            $this->call('migrate:fresh');
            $this->newLine();
        }

        $this->info('📦 Seeding comprehensive test data...');
        $this->call('db:seed', ['--class' => TestDataSeeder::class]);
        
        $this->newLine();
        $this->info('✅ Test data seeding completed!');
        $this->info('🎯 Your app is now ready for comprehensive Postman testing!');
        
        return 0;
    }
} 