<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Nurse;
use Laravel\Sanctum\Sanctum;

class NurseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']); // Seed roles
    }

    /**
     * Test that an admin can list all nurses.
     */
    public function test_admin_can_list_all_nurses()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        Nurse::factory()->count(3)->create();

        $response = $this->getJson('/api/nurses');

        $response->assertStatus(200)
                 ->assertJsonStructure(['nurses' => [['id', 'name', 'phone_number', 'address']]]);
    }

    /**
     * Test that an admin can create a nurse.
     */
    public function test_admin_can_create_nurse()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/nurses', [
            'name' => 'Nurse Jane',
            'phone_number' => '1234567890',
            'address' => '123 Nurse Lane',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Nurse added successfully.']);

        $this->assertDatabaseHas('nurses', ['name' => 'Nurse Jane']);
    }

    /**
     * Test that an admin can update a nurse.
     */
    public function test_admin_can_update_nurse()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        $nurse = Nurse::factory()->create();

        $response = $this->putJson("/api/admin/nurses/{$nurse->id}", [
            'name' => 'Updated Nurse',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Nurse updated successfully.']);

        $this->assertEquals('Updated Nurse', $nurse->fresh()->name);
    }

    /**
     * Test that an admin can delete a nurse.
     */
    public function test_admin_can_delete_nurse()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        Sanctum::actingAs($admin);

        $nurse = Nurse::factory()->create();

        $response = $this->deleteJson("/api/admin/nurses/{$nurse->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Nurse deleted successfully.']);

        $this->assertDatabaseMissing('nurses', ['id' => $nurse->id]);
    }

    /**
     * Test that a user cannot create a nurse.
     */
    public function test_user_cannot_create_nurse()
    {
        $userRole = Role::where('name', 'user')->first();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/nurses', [
            'name' => 'Nurse Jane',
            'phone_number' => '1234567890',
            'address' => '123 Nurse Lane',
        ]);

        $response->assertStatus(403); // Forbidden for users
    }

    // Add other tests if necessary, such as showing a specific nurse, etc.
    /**
 * Test that a user can list all nurses.
 */
public function test_user_can_list_all_nurses()
{
    $userRole = Role::where('name', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id]);

    Sanctum::actingAs($user);

    Nurse::factory()->count(3)->create();

    $response = $this->getJson('/api/nurses');

    $response->assertStatus(200)
             ->assertJsonStructure(['nurses' => [['id', 'name', 'phone_number', 'address']]]);
}

/**
 * Test that a user can view a specific nurse.
 */
public function test_user_can_view_specific_nurse()
{
    $userRole = Role::where('name', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id]);

    Sanctum::actingAs($user);

    $nurse = Nurse::factory()->create();

    $response = $this->getJson("/api/nurses/{$nurse->id}");

    $response->assertStatus(200)
             ->assertJson(['nurse' => [
                 'id' => $nurse->id,
                 'name' => $nurse->name,
                 'phone_number' => $nurse->phone_number,
                 'address' => $nurse->address,
             ]]);
}

}