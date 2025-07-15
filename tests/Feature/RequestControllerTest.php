<?php

namespace Tests\Feature;

use App\Models\Request;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private array $services;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users once for all tests
        $this->user = User::factory()->create(['role_id' => 2]); // Regular user
        $this->admin = User::factory()->create(['role_id' => 1]); // Admin
        
        // Create services once for all tests
        $this->services = Service::factory(2)->create()->pluck('id')->toArray();
    }

    public function test_user_can_create_request(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/requests', [
            'full_name' => 'John Doe',
            'phone_number' => '1234567890',
            'location' => 'Test Location',
            'time_type' => Request::TIME_TYPE_FULL,
            'nurse_gender' => 'female',
            'service_ids' => $this->services,
            'problem_description' => 'Test problem',
            'scheduled_time' => now()->addDay()->toDateTimeString(),
            'ending_time' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'full_name',
                'services'
            ]);

        $this->assertDatabaseHas('requests', [
            'full_name' => 'John Doe',
            'user_id' => $this->user->id,
            'status' => Request::STATUS_PENDING
        ]);
    }

    public function test_user_can_view_own_requests(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/requests');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $request->id]);
    }

    public function test_admin_can_view_all_requests(): void
    {
        $requests = Request::factory(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/requests');

        $response->assertStatus(200);
        foreach ($requests as $request) {
            $response->assertJsonFragment(['id' => $request->id]);
        }
    }

    public function test_user_can_update_own_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create(['status' => Request::STATUS_PENDING]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/requests/{$request->id}", [
                'full_name' => 'Updated Name',
                'problem_description' => 'Updated Description'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'full_name' => 'Updated Name',
                'problem_description' => 'Updated Description'
            ]);
    }

    public function test_admin_can_update_request_with_time_needed(): void
    {
        $request = Request::factory()->create(['status' => Request::STATUS_PENDING]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/requests/{$request->id}", [
                'status' => Request::STATUS_APPROVED,
                'time_needed_to_arrive' => 30
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => Request::STATUS_APPROVED
            ]);

        // Get request to verify time_needed_to_arrive is cached
        $showResponse = $this->actingAs($this->admin)
            ->getJson("/api/requests/{$request->id}");

        $showResponse->assertStatus(200)
            ->assertJsonFragment([
                'time_needed_to_arrive' => 30
            ]);
    }

    public function test_unauthorized_user_cannot_view_others_request(): void
    {
        $otherUser = User::factory()->create(['role_id' => 2]);
        $request = Request::factory()
            ->for($otherUser)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/requests/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_invalid_request_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/requests', [
                'full_name' => '', // Required field
                'service_ids' => [] // Empty array
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'service_ids']);
    }

    public function test_admin_can_soft_delete_request(): void
    {
        $request = Request::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/requests/{$request->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Request removed from admin view, but still available to users.']);

        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }

    public function test_user_can_view_soft_deleted_own_request(): void
    {
        $request = Request::factory()
            ->for($this->user)
            ->create();

        // Admin soft deletes the request
        $this->actingAs($this->admin)
            ->deleteJson("/api/requests/{$request->id}");

        // User should still be able to view it
        $response = $this->actingAs($this->user)
            ->getJson("/api/requests/{$request->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $request->id]);
    }

    public function test_admin_cannot_view_soft_deleted_request(): void
    {
        $request = Request::factory()->create();

        // Admin soft deletes the request
        $this->actingAs($this->admin)
            ->deleteJson("/api/requests/{$request->id}");

        // Admin should not be able to view it
        $response = $this->actingAs($this->admin)
            ->getJson("/api/requests/{$request->id}");

        $response->assertStatus(404);
    }
}