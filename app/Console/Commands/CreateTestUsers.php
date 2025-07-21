<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test users for admin and normal user roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating test users...');

        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['id' => 1, 'name' => 'admin']);
        $userRole = Role::firstOrCreate(['id' => 2, 'name' => 'user']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@test.com',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'location' => 'Test Location',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'phone_number' => '+1234567890',
                'confirmation_code' => null,
                'confirmation_code_expires_at' => null,
                'email_verified_at' => now(),
            ]
        );

        // Create normal user
        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Normal User',
                'email' => 'user@test.com',
                'password' => Hash::make('user123'),
                'role_id' => $userRole->id,
                'location' => 'User Location',
                'latitude' => 40.7580,
                'longitude' => -73.9855,
                'phone_number' => '+0987654321',
                'confirmation_code' => null,
                'confirmation_code_expires_at' => null,
                'email_verified_at' => now(),
            ]
        );

        $this->info('✅ Test users created successfully!');
        $this->line('🔴 Admin: admin@test.com / admin123');
        $this->line('🟢 User: user@test.com / user123');
        
        // Optionally create a test request with the new status
        $this->info('Creating sample request...');
        $request = \App\Models\Request::firstOrCreate(
            ['full_name' => 'Sample Request'],
            [
                'user_id' => $user->id,
                'full_name' => 'Sample Request',
                'phone_number' => '+1122334455',
                'name' => 'Sample Home Care Request',
                'problem_description' => 'Sample problem description for testing',
                'location' => 'Sample Location',
                'latitude' => 40.7489,
                'longitude' => -73.9680,
                'status' => \App\Models\Request::STATUS_SUBMITTED,
                'time_needed_to_arrive' => 30,
                'nurse_gender' => 'any',
                'time_type' => 'full-time',
                'scheduled_time' => now()->addHour(),
            ]
        );
        
        $this->info('✅ Sample request created with new status system!');
        
        return 0;
    }
} 