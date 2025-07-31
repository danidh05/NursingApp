<?php

namespace Tests\Feature;

use App\Models\FAQ;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminFAQControllerTest extends TestCase
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
    public function admin_can_view_all_faqs_including_inactive()
    {
        Sanctum::actingAs($this->admin);

        $activeFAQ = FAQ::factory()->active()->create();
        $inactiveFAQ = FAQ::factory()->inactive()->create();

        $response = $this->getJson('/api/admin/faqs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQs retrieved successfully'
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_create_new_faq()
    {
        Sanctum::actingAs($this->admin);

        $faqData = [
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.',
            'order' => 1,
            'is_active' => true
        ];

        $response = $this->postJson('/api/admin/faqs', $faqData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'FAQ created successfully',
                'data' => [
                    'question' => $faqData['question'],
                    'answer' => $faqData['answer'],
                    'order' => $faqData['order'],
                    'is_active' => $faqData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('faqs', $faqData);
    }

    /** @test */
    public function admin_can_create_faq_with_defaults()
    {
        Sanctum::actingAs($this->admin);

        $faqData = [
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.'
        ];

        $response = $this->postJson('/api/admin/faqs', $faqData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('faqs', [
            'question' => $faqData['question'],
            'answer' => $faqData['answer'],
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_update_faq()
    {
        Sanctum::actingAs($this->admin);

        $faq = FAQ::factory()->create();
        $updateData = [
            'question' => 'Updated question?',
            'answer' => 'Updated answer.',
            'order' => 5,
            'is_active' => false
        ];

        $response = $this->putJson("/api/admin/faqs/{$faq->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQ updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $updateData['question'],
                    'answer' => $updateData['answer'],
                    'order' => $updateData['order'],
                    'is_active' => $updateData['is_active']
                ]
            ]);

        $this->assertDatabaseHas('faqs', array_merge(['id' => $faq->id], $updateData));
    }

    /** @test */
    public function admin_can_delete_faq()
    {
        Sanctum::actingAs($this->admin);

        $faq = FAQ::factory()->create();

        $response = $this->deleteJson("/api/admin/faqs/{$faq->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQ deleted successfully'
            ]);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    /** @test */
    public function admin_can_toggle_faq_status()
    {
        Sanctum::actingAs($this->admin);

        $faq = FAQ::factory()->active()->create();

        $response = $this->patchJson("/api/admin/faqs/{$faq->id}/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQ status toggled successfully',
                'data' => [
                    'id' => $faq->id,
                    'is_active' => false
                ]
            ]);

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function admin_can_reorder_faqs()
    {
        Sanctum::actingAs($this->admin);

        $faq1 = FAQ::factory()->create(['order' => 1]);
        $faq2 = FAQ::factory()->create(['order' => 2]);
        $faq3 = FAQ::factory()->create(['order' => 3]);

        $reorderData = [
            'faq_ids' => [$faq3->id, $faq1->id, $faq2->id]
        ];

        $response = $this->postJson('/api/admin/faqs/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQs reordered successfully'
            ]);

        // Verify the new order
        $this->assertDatabaseHas('faqs', ['id' => $faq3->id, 'order' => 1]);
        $this->assertDatabaseHas('faqs', ['id' => $faq1->id, 'order' => 2]);
        $this->assertDatabaseHas('faqs', ['id' => $faq2->id, 'order' => 3]);
    }

    /** @test */
    public function regular_user_cannot_access_admin_faq_endpoints()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->create();

        // Test various admin endpoints
        $this->getJson('/api/admin/faqs')->assertStatus(403);
        $this->postJson('/api/admin/faqs', [])->assertStatus(403);
        $this->getJson("/api/admin/faqs/{$faq->id}")->assertStatus(403);
        $this->putJson("/api/admin/faqs/{$faq->id}", [])->assertStatus(403);
        $this->deleteJson("/api/admin/faqs/{$faq->id}")->assertStatus(403);
        $this->patchJson("/api/admin/faqs/{$faq->id}/toggle")->assertStatus(403);
        $this->postJson('/api/admin/faqs/reorder', [])->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_faq_endpoints()
    {
        $faq = FAQ::factory()->create();

        // Test various admin endpoints
        $this->getJson('/api/admin/faqs')->assertStatus(401);
        $this->postJson('/api/admin/faqs', [])->assertStatus(401);
        $this->getJson("/api/admin/faqs/{$faq->id}")->assertStatus(401);
        $this->putJson("/api/admin/faqs/{$faq->id}", [])->assertStatus(401);
        $this->deleteJson("/api/admin/faqs/{$faq->id}")->assertStatus(401);
        $this->patchJson("/api/admin/faqs/{$faq->id}/toggle")->assertStatus(401);
        $this->postJson('/api/admin/faqs/reorder', [])->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields_for_creating_faq()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/faqs', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer']);
    }

    /** @test */
    public function it_validates_field_lengths_for_creating_faq()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/faqs', [
            'question' => str_repeat('a', 256), // Too long
            'answer' => str_repeat('a', 2001), // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_faq_in_admin_endpoints()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/faqs/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'FAQ not found'
            ]);
    }

    /** @test */
    public function it_validates_reorder_request()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/faqs/reorder', [
            'faq_ids' => [999, 998] // Non-existent IDs
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['faq_ids.0', 'faq_ids.1']);
    }
}
