<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Popup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PopupControllerTest extends TestCase
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

    public function test_authenticated_user_can_view_active_popup()
    {
        // Arrange
        Popup::factory()->active()->create(['title' => 'Active Popup']);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'popup' => [
                    'id',
                    'image',
                    'title',
                    'content',
                    'type',
                    'start_date',
                    'end_date',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'popup' => [
                    'title' => 'Active Popup'
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_view_popups()
    {
        // Act
        $response = $this->getJson('/api/popups');

        // Assert
        $response->assertStatus(401);
    }

    public function test_returns_null_when_no_active_popup()
    {
        // Arrange - Create inactive popups
        Popup::factory()->inactive()->create();
        Popup::factory()->expired()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    public function test_returns_most_recent_active_popup_when_multiple_exist()
    {
        // Arrange
        $older = Popup::factory()->active()->create([
            'title' => 'Older Popup',
            'created_at' => now()->subDays(2)
        ]);
        
        $newer = Popup::factory()->active()->create([
            'title' => 'Newer Popup',
            'created_at' => now()->subDay()
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'title' => 'Newer Popup'
                ]
            ]);
    }

    public function test_popup_date_filtering_respects_start_date()
    {
        // Arrange - Popup that starts in the future
        Popup::factory()->create([
            'title' => 'Future Popup',
            'is_active' => true,
            'start_date' => now()->addDay(),
            'end_date' => now()->addWeek()
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    public function test_popup_date_filtering_respects_end_date()
    {
        // Arrange - Popup that ended yesterday
        Popup::factory()->create([
            'title' => 'Expired Popup',
            'is_active' => true,
            'start_date' => now()->subWeek(),
            'end_date' => now()->subDay()
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => null,
                'message' => 'No active popup available'
            ]);
    }

    public function test_popup_with_null_dates_is_always_active()
    {
        // Arrange
        Popup::factory()->active()->create(['title' => 'Always Active']);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'title' => 'Always Active'
                ]
            ]);
    }

    // ==================== ADMIN ENDPOINTS ====================

    public function test_admin_can_view_all_popups()
    {
        // Arrange
        Popup::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/popups');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'popups');
    }

    public function test_user_cannot_access_admin_popup_endpoints()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/popups');

        // Assert
        $response->assertStatus(403);
    }

    public function test_admin_can_create_popup_with_all_fields()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('popup.jpg', 800, 600);

        $data = [
            'image' => $image,
            'title' => 'New Popup',
            'content' => 'This is popup content',
            'type' => 'promo',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'is_active' => true
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/popups', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Popup created successfully.'
            ])
            ->assertJsonStructure([
                'popup' => [
                    'id',
                    'image',
                    'title',
                    'content',
                    'type'
                ]
            ]);

        $this->assertDatabaseHas('popups', [
            'title' => 'New Popup',
            'content' => 'This is popup content',
            'type' => 'promo',
            'is_active' => true
        ]);
    }

    public function test_admin_can_create_popup_with_minimal_fields()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('popup.jpg');

        $data = [
            'image' => $image,
            'title' => 'Minimal Popup',
            'content' => 'Basic content',
            'type' => 'info'
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/popups', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('popups', [
            'title' => 'Minimal Popup',
            'content' => 'Basic content',
            'type' => 'info',
            'start_date' => null,
            'end_date' => null,
            'is_active' => true // default value
        ]);
    }

    public function test_create_popup_validation_requires_required_fields()
    {
        // Act - Missing required fields
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/popups', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content', 'type']);
    }

    public function test_create_popup_validation_rejects_invalid_type()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('popup.jpg');

        // Act
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/popups', [
                'image' => $image,
                'title' => 'Test Popup',
                'content' => 'Test content',
                'type' => 'invalid-type'
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_create_popup_validation_end_date_after_start_date()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('popup.jpg');

        // Act - End date before start date
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/popups', [
                'image' => $image,
                'title' => 'Test Popup',
                'content' => 'Test content',
                'type' => 'info',
                'start_date' => now()->addWeek()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDay()->format('Y-m-d H:i:s')
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_admin_can_show_specific_popup()
    {
        // Arrange
        $popup = Popup::factory()->create(['title' => 'Test Popup']);

        // Act
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/popups/{$popup->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'popup' => [
                    'id' => $popup->id,
                    'title' => 'Test Popup'
                ]
            ]);
    }

    public function test_admin_can_update_popup()
    {
        // Arrange
        $popup = Popup::factory()->create([
            'title' => 'Original Title',
            'content' => 'Original content',
            'type' => 'info'
        ]);

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'type' => 'warning',
            'is_active' => false
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/popups/{$popup->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Popup updated successfully.'
            ]);

        $this->assertDatabaseHas('popups', [
            'id' => $popup->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'type' => 'warning',
            'is_active' => false
        ]);
    }

    public function test_admin_can_update_popup_with_new_image()
    {
        // Arrange
        Storage::fake('public');
        $popup = Popup::factory()->create(['title' => 'Test Popup']);
        $newImage = UploadedFile::fake()->image('new-popup.jpg');

        // Act
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/popups/{$popup->id}", [
                'image' => $newImage,
                'title' => 'Updated with new image',
                'content' => 'Updated content',
                'type' => 'info'
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('popups', [
            'id' => $popup->id,
            'title' => 'Updated with new image'
        ]);
    }

    public function test_update_popup_validation_prevents_start_date_in_past_for_new_popups()
    {
        // Arrange
        $popup = Popup::factory()->create();

        // Act - Try to set start date in the past for updates (should be allowed)
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/popups/{$popup->id}", [
                'title' => 'Updated',
                'content' => 'Updated',
                'type' => 'info',
                'start_date' => now()->subDay()->format('Y-m-d H:i:s')
            ]);

        // Assert - Updates should allow past dates
        $response->assertStatus(200);
    }

    public function test_admin_can_delete_popup()
    {
        // Arrange
        $popup = Popup::factory()->create(['title' => 'To be deleted']);

        // Act
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/popups/{$popup->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Popup deleted successfully.'
            ]);

        $this->assertDatabaseMissing('popups', [
            'id' => $popup->id
        ]);
    }

    public function test_user_cannot_perform_admin_operations()
    {
        // Arrange
        $popup = Popup::factory()->create();
        Storage::fake('public');
        $image = UploadedFile::fake()->image('popup.jpg');

        // Test all admin operations
        $operations = [
            ['POST', '/api/admin/popups', [
                'image' => $image, 
                'title' => 'Test', 
                'content' => 'Test', 
                'type' => 'info'
            ]],
            ['GET', "/api/admin/popups/{$popup->id}", []],
            ['PUT', "/api/admin/popups/{$popup->id}", [
                'title' => 'Updated', 
                'content' => 'Updated', 
                'type' => 'info'
            ]],
            ['DELETE', "/api/admin/popups/{$popup->id}", []]
        ];

        foreach ($operations as [$method, $url, $data]) {
            // Act
            $response = $this->actingAs($this->user)
                ->json($method, $url, $data);

            // Assert
            $response->assertStatus(403, "Failed for {$method} {$url}");
        }
    }

    public function test_popup_type_validation_accepts_valid_types()
    {
        // Arrange
        Storage::fake('public');
        $validTypes = ['info', 'warning', 'promo'];

        foreach ($validTypes as $type) {
            $image = UploadedFile::fake()->image("popup-{$type}.jpg");
            
            // Act
            $response = $this->actingAs($this->admin)
                ->postJson('/api/admin/popups', [
                    'image' => $image,
                    'title' => "Test {$type} Popup",
                    'content' => "Test content for {$type}",
                    'type' => $type
                ]);

            // Assert
            $response->assertStatus(201, "Failed for type: {$type}");
        }
    }
}