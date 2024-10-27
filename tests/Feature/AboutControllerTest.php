<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\About;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;


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
            'whatsapp_numbers' => ['+00000000000'], // Changed to an array of WhatsApp numbers
            'description' => 'Old description',
            'tiktok_url' => 'https://oldtiktok.com', // Added tiktok_url field
        ]);
    
        Sanctum::actingAs($admin);
    
        // Act
        $response = $this->putJson('/api/admin/about', [
            'online_shop_url' => 'https://newshop.com',
            'facebook_url' => 'https://newfacebook.com',
            'instagram_url' => 'https://newinstagram.com',
            'whatsapp_numbers' => ['+1234567890', '+0987654321'], // Updated to multiple numbers
            'description' => 'New description',
            'tiktok_url' => 'https://newtiktok.com', // Updated tiktok_url field
        ]);
    
        // Assert
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'About Us updated successfully.',
                     'about' => [
                         'online_shop_url' => 'https://newshop.com',
                         'facebook_url' => 'https://newfacebook.com',
                         'instagram_url' => 'https://newinstagram.com',
                         'whatsapp_numbers' => ['+1234567890', '+0987654321'], // Check array of WhatsApp numbers
                         'description' => 'New description',
                         'tiktok_url' => 'https://newtiktok.com', // Check updated tiktok_url field
                     ],
                 ]);
    
        // Check other fields in the database
        $this->assertDatabaseHas('abouts', [
            'online_shop_url' => 'https://newshop.com',
            'facebook_url' => 'https://newfacebook.com',
            'instagram_url' => 'https://newinstagram.com',
            'description' => 'New description',
            'tiktok_url' => 'https://newtiktok.com',
        ]);
    
        // Directly compare the whatsapp_numbers array from the database
        $this->assertEquals(
            ['+1234567890', '+0987654321'],
            $about->fresh()->whatsapp_numbers // No need for json_decode
        );
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