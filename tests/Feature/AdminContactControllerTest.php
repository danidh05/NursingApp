<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $this->admin = User::factory()->create();
        $this->admin->role_id = 1; // Admin role
        $this->admin->save();

        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
    }

    /** @test */
    public function admin_can_view_all_contact_submissions()
    {
        Sanctum::actingAs($this->admin);

        // Create some contact submissions
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/contacts');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contact submissions retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'second_name',
                        'full_name',
                        'address',
                        'description',
                        'phone_number',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'total_count'
            ]);

        $this->assertEquals(3, $response->json('total_count'));
    }

    /** @test */
    public function admin_can_view_specific_contact_submission()
    {
        Sanctum::actingAs($this->admin);

        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St',
            'description' => 'Need nursing care',
            'phone_number' => '+1234567890',
        ]);

        $response = $this->getJson("/api/admin/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contact submission retrieved successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'contact' => [
                        'id',
                        'first_name',
                        'second_name',
                        'full_name',
                        'address',
                        'description',
                        'phone_number',
                        'created_at',
                        'updated_at',
                    ],
                    'created_at_formatted',
                    'days_ago'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals('John', $data['contact']['first_name']);
        $this->assertEquals('Doe', $data['contact']['second_name']);
        $this->assertEquals('John Doe', $data['contact']['full_name']);
        $this->assertEquals('123 Main St', $data['contact']['address']);
        $this->assertEquals('Need nursing care', $data['contact']['description']);
        $this->assertEquals('+1234567890', $data['contact']['phone_number']);
    }

    /** @test */
    public function admin_can_delete_contact_submission()
    {
        Sanctum::actingAs($this->admin);

        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/admin/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contact submission deleted successfully',
            ]);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function admin_gets_404_when_contact_submission_not_found()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/contacts/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Contact submission not found',
            ]);
    }

    /** @test */
    public function admin_gets_404_when_deleting_nonexistent_contact_submission()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson('/api/admin/contacts/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Contact submission not found',
            ]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_contact_routes()
    {
        Sanctum::actingAs($this->user);

        $contact = Contact::factory()->create();

        // Try to view all contacts
        $response = $this->getJson('/api/admin/contacts');
        $response->assertStatus(403);

        // Try to view specific contact
        $response = $this->getJson("/api/admin/contacts/{$contact->id}");
        $response->assertStatus(403);

        // Try to delete contact
        $response = $this->deleteJson("/api/admin/contacts/{$contact->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_contact_routes()
    {
        $contact = Contact::factory()->create();

        // Try to view all contacts
        $response = $this->getJson('/api/admin/contacts');
        $response->assertStatus(401);

        // Try to view specific contact
        $response = $this->getJson("/api/admin/contacts/{$contact->id}");
        $response->assertStatus(401);

        // Try to delete contact
        $response = $this->deleteJson("/api/admin/contacts/{$contact->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_view_contact_submissions_in_correct_order()
    {
        Sanctum::actingAs($this->admin);

        // Create contacts with different creation times
        $oldContact = Contact::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        
        $newContact = Contact::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/contacts');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Should be ordered by latest first
        $this->assertEquals($newContact->id, $data[0]['id']);
        $this->assertEquals($oldContact->id, $data[1]['id']);
    }

    /** @test */
    public function admin_can_view_contact_with_formatted_details()
    {
        Sanctum::actingAs($this->admin);

        $contact = Contact::factory()->create([
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->getJson("/api/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        $this->assertArrayHasKey('contact', $data);
        $this->assertArrayHasKey('created_at_formatted', $data);
        $this->assertArrayHasKey('days_ago', $data);
        $this->assertGreaterThanOrEqual(0, $data['days_ago']);
        $this->assertLessThanOrEqual(2, $data['days_ago']); // Allow for small time differences
    }
} 