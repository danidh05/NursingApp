<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Settings;
use App\Models\Role;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->role()->associate($adminRole);
        $this->adminUser->save();

        // Create regular user
        $userRole = Role::create(['name' => 'user']);
        $this->regularUser = User::factory()->create();
        $this->regularUser->role()->associate($userRole);
        $this->regularUser->save();
    }

    /** @test */
    public function admin_can_view_all_settings()
    {
        // Create some settings
        Settings::factory()->create(['key' => 'job_application_url', 'value' => 'https://example.com/jobs']);
        Settings::factory()->create(['key' => 'whatsapp_support_number', 'value' => '+1234567890']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'type',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function admin_can_view_specific_setting()
    {
        $setting = Settings::factory()->create([
            'key' => 'job_application_url',
            'value' => 'https://example.com/jobs'
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/settings/{$setting->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $setting->id,
                    'key' => 'job_application_url',
                    'value' => 'https://example.com/jobs'
                ]
            ]);
    }

    /** @test */
    public function admin_can_create_new_setting()
    {
        $settingData = [
            'key' => 'new_setting',
            'value' => 'new_value',
            'type' => 'string',
            'description' => 'A new setting',
            'is_active' => true
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/settings', $settingData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'key' => 'new_setting',
                    'value' => 'new_value',
                    'type' => 'string',
                    'description' => 'A new setting',
                    'is_active' => true
                ]
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'new_setting',
            'value' => 'new_value'
        ]);
    }

    /** @test */
    public function admin_can_update_setting()
    {
        $setting = Settings::factory()->create([
            'key' => 'job_application_url',
            'value' => 'https://old-example.com/jobs'
        ]);

        $updateData = [
            'value' => 'https://new-example.com/jobs',
            'description' => 'Updated job application URL'
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/settings/{$setting->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $setting->id,
                    'key' => 'job_application_url',
                    'value' => 'https://new-example.com/jobs',
                    'description' => 'Updated job application URL'
                ]
            ]);

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'value' => 'https://new-example.com/jobs'
        ]);
    }

    /** @test */
    public function admin_can_delete_setting()
    {
        $setting = Settings::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/settings/{$setting->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Setting deleted successfully']);

        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }

    /** @test */
    public function admin_can_toggle_setting_active_status()
    {
        $setting = Settings::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/admin/settings/{$setting->id}/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $setting->id,
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function public_can_access_public_settings()
    {
        // Create settings
        Settings::factory()->create([
            'key' => 'job_application_url',
            'value' => 'https://example.com/jobs',
            'is_active' => true
        ]);
        Settings::factory()->create([
            'key' => 'whatsapp_support_number',
            'value' => '+1234567890',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/settings/public');

        $response->assertStatus(200)
            ->assertJson([
                'job_application_url' => 'https://example.com/jobs',
                'whatsapp_support_number' => '+1234567890'
            ]);
    }

    /** @test */
    public function public_settings_only_returns_active_settings()
    {
        // Create active and inactive settings
        Settings::factory()->create([
            'key' => 'job_application_url',
            'value' => 'https://example.com/jobs',
            'is_active' => true
        ]);
        Settings::factory()->create([
            'key' => 'whatsapp_support_number',
            'value' => '+1234567890',
            'is_active' => false
        ]);

        $response = $this->getJson('/api/settings/public');

        $response->assertStatus(200)
            ->assertJson([
                'job_application_url' => 'https://example.com/jobs',
                'whatsapp_support_number' => null
            ]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_settings_endpoints()
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/admin/settings');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_settings_endpoints()
    {
        $response = $this->getJson('/api/admin/settings');

        $response->assertStatus(401);
    }

    /** @test */
    public function setting_creation_requires_valid_data()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/settings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'value']);
    }

    /** @test */
    public function setting_key_must_be_unique()
    {
        Settings::factory()->create(['key' => 'existing_key']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/settings', [
                'key' => 'existing_key',
                'value' => 'some_value'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    /** @test */
    public function setting_type_must_be_valid()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/settings', [
                'key' => 'test_key',
                'value' => 'test_value',
                'type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function admin_can_update_job_application_url()
    {
        $setting = Settings::factory()->create([
            'key' => 'job_application_url',
            'value' => 'https://old-example.com/jobs'
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/settings/{$setting->id}", [
                'value' => 'https://new-example.com/careers'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'key' => 'job_application_url',
            'value' => 'https://new-example.com/careers'
        ]);
    }

    /** @test */
    public function admin_can_update_whatsapp_support_number()
    {
        $setting = Settings::factory()->create([
            'key' => 'whatsapp_support_number',
            'value' => '+1234567890'
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/settings/{$setting->id}", [
                'value' => '+9876543210'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'key' => 'whatsapp_support_number',
            'value' => '+9876543210'
        ]);
    }

    /** @test */
    public function returns_404_for_nonexistent_setting()
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/settings/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function settings_service_methods_work_correctly()
    {
        $settingsService = app(\App\Services\SettingsService::class);

        // Test setting job application URL
        $settingsService->setJobApplicationUrl('https://test.com/jobs');
        $this->assertEquals('https://test.com/jobs', $settingsService->getJobApplicationUrl());

        // Test setting WhatsApp support number
        $settingsService->setWhatsAppSupportNumber('+1111111111');
        $this->assertEquals('+1111111111', $settingsService->getWhatsAppSupportNumber());

        // Test getting public settings
        $publicSettings = $settingsService->getPublicSettings();
        $this->assertArrayHasKey('job_application_url', $publicSettings);
        $this->assertArrayHasKey('whatsapp_support_number', $publicSettings);
    }
}