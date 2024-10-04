<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Ensure roles are seeded
    }

    /**
     * Test admin can list all services.
     */
    public function test_admin_can_list_all_services()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Assuming role_id 1 is admin
        Sanctum::actingAs($admin);

        Service::factory()->count(3)->create();

        $response = $this->getJson('/api/services');
        $response->assertStatus(200)
                 ->assertJsonStructure(['services' => [['id', 'name', 'price', 'description']]]);
    }

    /**
     * Test admin can create a service.
     */
    public function test_admin_can_create_service()
    {
        // Arrange: Create an admin user and authenticate them
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        Sanctum::actingAs($admin);

        // Act: Send a POST request to create a new service without category_id
        $response = $this->postJson('/api/admin/services', [
            'name' => 'New Service',
            'price' => 100,
            'description' => 'Service description.',
            'service_pic'=>'https://images.app.goo.gl/U8rVW2qT1GwMxkuP8',
            'discount_price' => 90
            // No category_id needed since it's now nullable
        ]);

        // Assert: Check the response and database state
        $response->assertStatus(201) // Check if the response status is 201 Created
                 ->assertJson([
                     'message' => 'Service created successfully.',
                     'service' => [
                         'name' => 'New Service',
                         'price' => '100.00', // Match the string representation in JSON
                         'description' => 'Service description.',
                         // We won't explicitly check for 'category_id' here if it's not included in the response
                     ],
                 ]);

        // Assert that the service exists in the database
        $this->assertDatabaseHas('services', [
            'name' => 'New Service',
            'price' => 100, // The database should still store it as a numeric value
            'description' => 'Service description.',
            'discount_price' => 90

        ]);
    }

    /**
     * Test admin can update a service.
     */
    public function test_admin_can_update_service()
    {
        // Arrange: Create an admin and a service
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        Sanctum::actingAs($admin);

        // Create the service with initial data
        $service = Service::factory()->create([
            'name' => 'Old Service',
            'price' => 100,
            'description' => 'Old description.',
            'category_id' => null, // Start with no category
            'service_pic'=>'photo2.jpg',
            'discount_price' => 92

        ]);

        // Act: Send a PUT request to update the service
        $response = $this->putJson("/api/admin/services/{$service->id}", [
            'name' => 'Updated Service', // Ensure this matches the controller's validation requirements
            'price' => 150, // Example price change
            'description' => 'Updated description.',
            'discount_price' => 92

            // No category_id since it's not mandatory
        ]);

        // Assert: Check response and database for the updated service details
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Service updated successfully.']);

        // Fetch the latest data from the database and assert changes
        $updatedService = $service->fresh();
        $this->assertEquals('Updated Service', $updatedService->name);
        $this->assertEquals(150, $updatedService->price);
        $this->assertEquals('Updated description.', $updatedService->description);
        $this->assertNull($updatedService->category_id); // Ensure category_id remains null
    }

    /**
     * Test admin can delete a service.
     */
    public function test_admin_can_delete_service()
    {
        // Arrange: Create an admin and a service
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $service = Service::factory()->create();
        Sanctum::actingAs($admin);

        // Act: Delete the service
        $response = $this->deleteJson("/api/admin/services/{$service->id}");

        // Assert: Check response
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Service deleted successfully.']);

        // Assert the service is no longer in the database (hard delete check)
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    /**
     * Test user can view services.
     */
    public function test_user_can_view_services()
    {
        $user = User::factory()->create(['role_id' => 2]); // User role
        Sanctum::actingAs($user);

        Service::factory()->count(3)->create();

        $response = $this->getJson('/api/services');
        $response->assertStatus(200)
                 ->assertJsonStructure(['services' => [['id', 'name', 'price', 'description']]]);
    }

    /**
     * Test user cannot create, update, or delete services.
     */
    public function test_user_cannot_create_update_delete_services()
    {
        $user = User::factory()->create(['role_id' => 2]); // User role
        Sanctum::actingAs($user);

        $service = Service::factory()->create();

        // Attempt to create
        $createResponse = $this->postJson('/api/admin/services', [
            'name' => 'Unauthorized Service',
            'price' => 100,
            'description' => 'Unauthorized description.',
        ]);
        $createResponse->assertStatus(403); // Forbidden

        // Attempt to update
        $updateResponse = $this->putJson("/api/admin/services/{$service->id}", [
            'name' => 'Updated Unauthorized Service',
        ]);
        $updateResponse->assertStatus(403); // Forbidden

        // Attempt to delete
        $deleteResponse = $this->deleteJson("/api/admin/services/{$service->id}");
        $deleteResponse->assertStatus(403); // Forbidden
    }

    /**
     * Test that a non-admin user cannot create a service.
     */
    public function test_non_admin_cannot_create_service()
    {
        // Arrange: Create a regular user and authenticate them
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        Sanctum::actingAs($user);

        // Act: Attempt to create a new service as a non-admin user
        $response = $this->postJson('/api/admin/services', [
            'name' => 'Unauthorized Service',
            'price' => 100,
            'description' => 'Service description.',
        ]);

        // Assert: Ensure the request is forbidden (403 Forbidden)
        $response->assertStatus(403);

        // Assert that the service does not exist in the database
        $this->assertDatabaseMissing('services', [
            'name' => 'Unauthorized Service',
        ]);
    }

    /**
     * Test validation rules when creating a service.
     */
    public function test_create_service_validation_errors()
    {
        // Arrange: Create an admin user and authenticate them
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        Sanctum::actingAs($admin);

        // Act: Attempt to create a service with missing required fields
        $response = $this->postJson('/api/admin/services', [
            'name' => '', // Invalid: required field
            'price' => -10, // Invalid: price cannot be negative
        ]);

        // Assert: Check for validation errors
        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['name', 'price']); // Removed 'category_id' since it's not required
    }
}