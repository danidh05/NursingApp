<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\About;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;


class AboutControllerTest extends TestCase
{
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_admin_can_update_about()
    {
        // Arrange
        $admin = User::factory()->create(['role_id' => 1]); // Assuming 1 is admin role
        $about = About::factory()->create([
            'online_shop_url' => 'https://oldshop.com',
            'facebook_url' => 'https://oldfacebook.com',
            'instagram_url' => 'https://oldinstagram.com',
            'whatsapp_number' => '+00000000000',
            'description' => 'Old description',
        ]);

        Sanctum::actingAs($admin);

        // Act
        $response = $this->putJson('/api/admin/about', [
            'online_shop_url' => 'https://newshop.com',
            'facebook_url' => 'https://newfacebook.com',
            'instagram_url' => 'https://newinstagram.com',
            'whatsapp_number' => '+1234567890',
            'description' => 'New description',
        ]);

        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'About Us updated successfully.',
                     'about' => [
                         'online_shop_url' => 'https://newshop.com',
                         'facebook_url' => 'https://newfacebook.com',
                         'instagram_url' => 'https://newinstagram.com',
                         'whatsapp_number' => '+1234567890',
                         'description' => 'New description',
                     ],
                 ]);

        $this->assertDatabaseHas('abouts', [
            'online_shop_url' => 'https://newshop.com',
            'facebook_url' => 'https://newfacebook.com',
            'instagram_url' => 'https://newinstagram.com',
            'whatsapp_number' => '+1234567890',
            'description' => 'New description',
        ]);
    }

    public function test_non_admin_cannot_update_about()
    {
        // Arrange
        $user = User::factory()->create(['role_id' => 2]); // Assuming 2 is user role
        $about = About::factory()->create();

        Sanctum::actingAs($user);

        // Act
        $response = $this->putJson('/api/admin/about', [
            'online_shop_url' => 'https://newshop.com',
        ]);

        // Assert
        $response->assertStatus(403); // Forbidden for non-admins
    }
}