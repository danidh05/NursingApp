<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceAreaPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAreaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->role_id = 1; // Admin role
        $this->admin->save();

        // Create regular user
        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
    }

    /** @test */
    public function admin_can_view_all_areas_with_user_counts()
    {
        Sanctum::actingAs($this->admin);

        $area1 = Area::factory()->create(['name' => 'Downtown']);
        $area2 = Area::factory()->create(['name' => 'Uptown']);

        // Assign users to areas
        $this->user->update(['area_id' => $area1->id]);
        $user2 = User::factory()->create(['area_id' => $area1->id]);

        $response = $this->getJson('/api/admin/areas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Areas retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');

        // Check that areas are ordered by name
        $response->assertJsonPath('data.0.name', 'Downtown')
            ->assertJsonPath('data.1.name', 'Uptown');

        // Check user counts
        $response->assertJsonPath('data.0.users_count', 2)
            ->assertJsonPath('data.1.users_count', 0);
    }

    /** @test */
    public function admin_can_create_new_area()
    {
        Sanctum::actingAs($this->admin);

        $areaData = [
            'name' => 'New Area'
        ];

        $response = $this->postJson('/api/admin/areas', $areaData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Area created successfully',
                'data' => [
                    'name' => $areaData['name']
                ]
            ]);

        $this->assertDatabaseHas('areas', $areaData);
    }

    /** @test */
    public function admin_can_update_area()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson("/api/admin/areas/{$area->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Area updated successfully',
                'data' => [
                    'id' => $area->id,
                    'name' => $updateData['name']
                ]
            ]);

        $this->assertDatabaseHas('areas', array_merge(['id' => $area->id], $updateData));
    }

    /** @test */
    public function admin_can_view_area_with_details()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Test Area']);
        $service = Service::factory()->create(['name' => 'Test Service']);

        // Assign user to area
        $this->user->update(['area_id' => $area->id]);

        // Create service price for area
        ServiceAreaPrice::factory()->create([
            'service_id' => $service->id,
            'area_id' => $area->id,
            'price' => 50.00
        ]);

        $response = $this->getJson("/api/admin/areas/{$area->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Area retrieved successfully',
                'data' => [
                    'area' => [
                        'id' => $area->id,
                        'name' => $area->name
                    ],
                    'user_count' => 1,
                    'service_price_count' => 1
                ]
            ]);

        // Check that users and service prices are included
        $response->assertJsonPath('data.users.0.id', $this->user->id)
            ->assertJsonPath('data.service_prices.0.service_name', 'Test Service');
    }

    /** @test */
    public function admin_can_delete_area_with_no_users_or_service_prices()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Empty Area']);

        $response = $this->deleteJson("/api/admin/areas/{$area->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Area deleted successfully'
            ]);

        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    /** @test */
    public function admin_cannot_delete_area_with_users()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Area with Users']);
        $this->user->update(['area_id' => $area->id]);

        $response = $this->deleteJson("/api/admin/areas/{$area->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete area that has users assigned to it.'
            ]);

        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }

    /** @test */
    public function admin_cannot_delete_area_with_service_prices()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Area with Service Prices']);
        $service = Service::factory()->create();

        ServiceAreaPrice::factory()->create([
            'service_id' => $service->id,
            'area_id' => $area->id,
            'price' => 50.00
        ]);

        $response = $this->deleteJson("/api/admin/areas/{$area->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete area that has service prices configured.'
            ]);

        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_area_endpoints()
    {
        Sanctum::actingAs($this->user);

        $area = Area::factory()->create();

        // Test various admin endpoints
        $this->getJson('/api/admin/areas')->assertStatus(403);
        $this->postJson('/api/admin/areas', [])->assertStatus(403);
        $this->getJson("/api/admin/areas/{$area->id}")->assertStatus(403);
        $this->putJson("/api/admin/areas/{$area->id}", [])->assertStatus(403);
        $this->deleteJson("/api/admin/areas/{$area->id}")->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_area_endpoints()
    {
        $area = Area::factory()->create();

        // Test various admin endpoints
        $this->getJson('/api/admin/areas')->assertStatus(401);
        $this->postJson('/api/admin/areas', [])->assertStatus(401);
        $this->getJson("/api/admin/areas/{$area->id}")->assertStatus(401);
        $this->putJson("/api/admin/areas/{$area->id}", [])->assertStatus(401);
        $this->deleteJson("/api/admin/areas/{$area->id}")->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields_for_creating_area()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/areas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_unique_area_name()
    {
        Sanctum::actingAs($this->admin);

        $existingArea = Area::factory()->create(['name' => 'Existing Area']);

        $response = $this->postJson('/api/admin/areas', [
            'name' => 'Existing Area'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_field_lengths_for_creating_area()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/areas', [
            'name' => str_repeat('a', 256) // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_area()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/areas/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Area not found'
            ]);
    }

    /** @test */
    public function it_validates_unique_area_name_on_update()
    {
        Sanctum::actingAs($this->admin);

        $area1 = Area::factory()->create(['name' => 'Area 1']);
        $area2 = Area::factory()->create(['name' => 'Area 2']);

        $response = $this->putJson("/api/admin/areas/{$area1->id}", [
            'name' => 'Area 2' // Try to use name from area2
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_allows_same_name_on_update_same_area()
    {
        Sanctum::actingAs($this->admin);

        $area = Area::factory()->create(['name' => 'Test Area']);

        $response = $this->putJson("/api/admin/areas/{$area->id}", [
            'name' => 'Test Area' // Same name
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Area updated successfully'
            ]);
    }
}
