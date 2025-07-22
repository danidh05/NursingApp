<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Slider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SliderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        // Create test users
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();
        
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->user = User::factory()->create(['role_id' => $userRole->id]);
    }

    // ==================== API ENDPOINTS (Public/User Access) ====================

    public function test_authenticated_user_can_view_sliders()
    {
        // Arrange
        Slider::factory()->create(['position' => 1, 'title' => 'First Slider']);
        Slider::factory()->create(['position' => 2, 'title' => 'Second Slider']);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/sliders');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'sliders' => [
                    '*' => [
                        'id',
                        'image',
                        'title',
                        'subtitle',
                        'position',
                        'link',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'sliders');
    }

    public function test_unauthenticated_user_cannot_view_sliders()
    {
        // Act
        $response = $this->getJson('/api/sliders');

        // Assert
        $response->assertStatus(401);
    }

    public function test_sliders_are_returned_ordered_by_position()
    {
        // Arrange
        Slider::factory()->create(['position' => 3, 'title' => 'Third']);
        Slider::factory()->create(['position' => 1, 'title' => 'First']);
        Slider::factory()->create(['position' => 2, 'title' => 'Second']);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/sliders');

        // Assert
        $response->assertStatus(200);
        $sliders = $response->json('sliders');
        
        $this->assertEquals('First', $sliders[0]['title']);
        $this->assertEquals('Second', $sliders[1]['title']);
        $this->assertEquals('Third', $sliders[2]['title']);
    }

    // ==================== ADMIN ENDPOINTS ====================

    public function test_admin_can_view_all_sliders()
    {
        // Arrange
        Slider::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/sliders');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'sliders');
    }

    public function test_user_cannot_access_admin_slider_endpoints()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/sliders');

        // Assert
        $response->assertStatus(403);
    }

    public function test_admin_can_create_slider_with_image()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('slider.jpg', 1200, 600);

        $data = [
            'image' => $image,
            'title' => 'New Slider',
            'subtitle' => 'Slider subtitle',
            'position' => 1,
            'link' => 'https://example.com'
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Slider created successfully.'
            ])
            ->assertJsonStructure([
                'slider' => [
                    'id',
                    'image',
                    'title',
                    'subtitle',
                    'position',
                    'link'
                ]
            ]);

        $this->assertDatabaseHas('sliders', [
            'title' => 'New Slider',
            'subtitle' => 'Slider subtitle',
            'position' => 1,
            'link' => 'https://example.com'
        ]);
    }

    public function test_admin_can_create_slider_without_optional_fields()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('slider.jpg');

        $data = [
            'image' => $image,
            'position' => 1
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('sliders', [
            'position' => 1,
            'title' => null,
            'subtitle' => null,
            'link' => null
        ]);
    }

    public function test_create_slider_validation_requires_image_and_position()
    {
        // Act - Missing required fields
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image', 'position']);
    }

    public function test_create_slider_validation_rejects_invalid_image()
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', [
                'image' => $file,
                'position' => 1
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_create_slider_validation_rejects_large_image()
    {
        // Arrange
        $image = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', [
                'image' => $image,
                'position' => 1
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_create_slider_validation_rejects_invalid_url()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('slider.jpg');

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sliders', [
                'image' => $image,
                'position' => 1,
                'link' => 'not-a-valid-url'
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['link']);
    }

    public function test_admin_can_show_specific_slider()
    {
        // Arrange
        $slider = Slider::factory()->create(['title' => 'Test Slider']);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/sliders/{$slider->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'slider' => [
                    'id' => $slider->id,
                    'title' => 'Test Slider'
                ]
            ]);
    }

    public function test_admin_show_slider_returns_404_for_nonexistent_slider()
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/sliders/999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_admin_can_update_slider()
    {
        // Arrange
        $slider = Slider::factory()->create([
            'title' => 'Original Title',
            'position' => 1
        ]);

        $data = [
            'title' => 'Updated Title',
            'position' => 2,
            'subtitle' => 'New subtitle'
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/sliders/{$slider->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Slider updated successfully.'
            ]);

        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'title' => 'Updated Title',
            'position' => 2,
            'subtitle' => 'New subtitle'
        ]);
    }

    public function test_admin_can_update_slider_with_new_image()
    {
        // Arrange
        Storage::fake('public');
        $slider = Slider::factory()->create(['title' => 'Test Slider']);
        $newImage = UploadedFile::fake()->image('new-slider.jpg');

        // Act
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/sliders/{$slider->id}", [
                'image' => $newImage,
                'title' => 'Updated with new image',
                'position' => 1
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('sliders', [
            'id' => $slider->id,
            'title' => 'Updated with new image'
        ]);
    }

    public function test_update_slider_validation_enforces_rules()
    {
        // Arrange
        $slider = Slider::factory()->create();

        // Act - Invalid position (negative)
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/sliders/{$slider->id}", [
                'position' => -1
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['position']);
    }

    public function test_admin_can_delete_slider()
    {
        // Arrange
        $slider = Slider::factory()->create(['title' => 'To be deleted']);

        // Act
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/sliders/{$slider->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Slider deleted successfully.'
            ]);

        $this->assertDatabaseMissing('sliders', [
            'id' => $slider->id
        ]);
    }

    public function test_delete_slider_returns_404_for_nonexistent_slider()
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/sliders/999');

        // Assert
        $response->assertStatus(404);
    }

    public function test_user_cannot_perform_admin_operations()
    {
        // Arrange
        $slider = Slider::factory()->create();
        Storage::fake('public');
        $image = UploadedFile::fake()->image('slider.jpg');

        // Test all admin operations
        $operations = [
            ['POST', '/api/admin/sliders', ['image' => $image, 'position' => 1]],
            ['GET', "/api/admin/sliders/{$slider->id}", []],
            ['PUT', "/api/admin/sliders/{$slider->id}", ['position' => 1]],
            ['DELETE', "/api/admin/sliders/{$slider->id}", []]
        ];

        foreach ($operations as [$method, $url, $data]) {
            // Act
            $response = $this->actingAs($this->user)
                ->json($method, $url, $data);

            // Assert
            $response->assertStatus(403, "Failed for {$method} {$url}");
        }
    }
}
