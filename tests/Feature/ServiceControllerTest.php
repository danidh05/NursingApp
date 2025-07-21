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

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Ensure roles are seeded
        $this->artisan('db:seed', ['--class' => 'TestDataSeeder']); // Seed categories and other test data
    }

    /**
     * Test admin can list all services.
     */
    public function test_admin_can_list_all_services()
    {
        $admin = User::factory()->create(['role_id' => 1]); // Assuming role_id 1 is admin
        Sanctum::actingAs($admin);

        // Note: Services are already created by TestDataSeeder

        $response = $this->getJson('/api/services');
        $response->assertStatus(200)
                 ->assertJsonStructure(['services' => [['id', 'name', 'price', 'description', 'category_id']]]);
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

        // Get a valid category_id
        $category = \App\Models\Category::first();

        // Act: Send a POST request to create a new service with required category_id
        $response = $this->postJson('/api/admin/services', [
            'name' => 'New Service',
            'price' => 100,
            'description' => 'A test service',
            'category_id' => $category->id, // Now required
        ]);

        // Assert: Check if the service was created successfully
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Service created successfully.'])
                 ->assertJsonStructure(['service' => ['id', 'name', 'price', 'description', 'category_id']]);
        
        // Verify the service exists in the database with correct category_id
        $this->assertDatabaseHas('services', [
            'name' => 'New Service',
            'price' => 100,
            'category_id' => $category->id
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

        // Get valid categories
        $category1 = \App\Models\Category::first();
        $category2 = \App\Models\Category::skip(1)->first();

        // Create the service with initial data and valid category
        $service = Service::factory()->create([
            'name' => 'Old Service',
            'price' => 100,
            'description' => 'Old description.',
            'category_id' => $category1->id, // Use valid category
            'service_pic' => 'photo2.jpg',
            'discount_price' => 92
        ]);

        // Act: Send a PUT request to update the service
        $response = $this->putJson("/api/admin/services/{$service->id}", [
            'name' => 'Updated Service',
            'price' => 150,
            'description' => 'Updated description.',
            'category_id' => $category2->id, // Update to different category
            'discount_price' => 92
        ]);

        // Assert: Check response and database for the updated service details
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Service updated successfully.']);

        // Fetch the latest data from the database and assert changes
        $updatedService = $service->fresh();
        $this->assertEquals('Updated Service', $updatedService->name);
        $this->assertEquals(150, $updatedService->price);
        $this->assertEquals('Updated description.', $updatedService->description);
        $this->assertEquals($category2->id, $updatedService->category_id); // Ensure category was updated
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
            // Missing category_id - now required
        ]);

        // Assert: Check for validation errors including category_id
        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['name', 'price', 'category_id']); // Now includes category_id as required
    }
}