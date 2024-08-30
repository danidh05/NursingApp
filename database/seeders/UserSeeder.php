<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'phone_number' => '1234567890',
            'location' => '123 Test St',
            'password' => Hash::make('password123'),
            'role_id' => 2, // Assuming '2' is the role_id for 'user'
            'email_verified_at' => now(), // Set as verified
        ]);
        
    }
}