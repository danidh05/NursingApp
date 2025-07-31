<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
    }

    /** @test */
    public function authenticated_user_can_submit_contact_form()
    {
        Sanctum::actingAs($this->user);

        $contactData = [
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St, New York, NY',
            'description' => 'I need nursing care for my elderly mother',
            'phone_number' => '+1234567890',
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Contact form submitted successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
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
            ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St, New York, NY',
            'description' => 'I need nursing care for my elderly mother',
            'phone_number' => '+1234567890',
        ]);
    }

    /** @test */
    public function authenticated_user_can_submit_contact_form_without_phone_number()
    {
        Sanctum::actingAs($this->user);

        $contactData = [
            'first_name' => 'Jane',
            'second_name' => 'Smith',
            'address' => '456 Oak Ave, Los Angeles, CA',
            'description' => 'Looking for pediatric nursing services',
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Contact form submitted successfully',
            ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'second_name' => 'Smith',
            'address' => '456 Oak Ave, Los Angeles, CA',
            'description' => 'Looking for pediatric nursing services',
            'phone_number' => null,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_submit_contact_form()
    {
        $contactData = [
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St, New York, NY',
            'description' => 'I need nursing care for my elderly mother',
            'phone_number' => '+1234567890',
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(401);
    }

    /** @test */
    public function contact_form_validation_works()
    {
        Sanctum::actingAs($this->user);

        // Missing required fields
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'second_name', 'address', 'description']);

        // Invalid data types
        $response = $this->postJson('/api/contact', [
            'first_name' => 123,
            'second_name' => '',
            'address' => 'a',
            'description' => '',
            'phone_number' => 123,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'second_name', 'description', 'phone_number']);
    }

    /** @test */
    public function contact_form_validates_field_lengths()
    {
        Sanctum::actingAs($this->user);

        $contactData = [
            'first_name' => str_repeat('a', 256), // Too long
            'second_name' => str_repeat('b', 256), // Too long
            'address' => str_repeat('c', 1001), // Too long
            'description' => str_repeat('d', 2001), // Too long
            'phone_number' => str_repeat('e', 21), // Too long
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'second_name', 'address', 'description', 'phone_number']);
    }

    /** @test */
    public function contact_form_returns_correct_data_structure()
    {
        Sanctum::actingAs($this->user);

        $contactData = [
            'first_name' => 'Alice',
            'second_name' => 'Johnson',
            'address' => '789 Pine St, Chicago, IL',
            'description' => 'Need home care for my father',
            'phone_number' => '+1987654321',
        ];

        $response = $this->postJson('/api/contact', $contactData);

        $response->assertStatus(201);
        
        $data = $response->json('data');
        
        $this->assertEquals('Alice', $data['first_name']);
        $this->assertEquals('Johnson', $data['second_name']);
        $this->assertEquals('Alice Johnson', $data['full_name']);
        $this->assertEquals('789 Pine St, Chicago, IL', $data['address']);
        $this->assertEquals('Need home care for my father', $data['description']);
        $this->assertEquals('+1987654321', $data['phone_number']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }
} 