<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Job application URL setting
        Settings::create([
            'key' => 'job_application_url',
            'value' => 'https://example.com/careers',
            'type' => 'url',
            'description' => 'URL for job application redirect',
            'is_active' => true,
        ]);

        // WhatsApp support number setting
        Settings::create([
            'key' => 'whatsapp_support_number',
            'value' => '+1234567890',
            'type' => 'phone',
            'description' => 'WhatsApp number for customer support',
            'is_active' => true,
        ]);

        // App name setting
        Settings::create([
            'key' => 'app_name',
            'value' => 'Nursing Care App',
            'type' => 'string',
            'description' => 'Application name',
            'is_active' => true,
        ]);

        // Support email setting
        Settings::create([
            'key' => 'support_email',
            'value' => 'support@example.com',
            'type' => 'email',
            'description' => 'Support email address',
            'is_active' => true,
        ]);
    }
}