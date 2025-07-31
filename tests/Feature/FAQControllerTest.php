<?php

namespace Tests\Feature;

use App\Models\FAQ;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FAQControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
    }

    /** @test */
    public function authenticated_user_can_view_active_faqs()
    {
        Sanctum::actingAs($this->user);

        // Create active and inactive FAQs
        $activeFAQ1 = FAQ::factory()->active()->create(['order' => 1]);
        $activeFAQ2 = FAQ::factory()->active()->create(['order' => 2]);
        $inactiveFAQ = FAQ::factory()->inactive()->create(['order' => 3]);

        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQs retrieved successfully'
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $activeFAQ1->id)
            ->assertJsonPath('data.1.id', $activeFAQ2->id);

        // Verify inactive FAQ is not included
        $response->assertJsonMissing([
            'data' => [
                ['id' => $inactiveFAQ->id]
            ]
        ]);
    }

    /** @test */
    public function it_returns_faqs_in_correct_order()
    {
        Sanctum::actingAs($this->user);

        // Create FAQs in reverse order
        $faq2 = FAQ::factory()->active()->create(['order' => 2]);
        $faq1 = FAQ::factory()->active()->create(['order' => 1]);
        $faq3 = FAQ::factory()->active()->create(['order' => 3]);

        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $faq1->id)
            ->assertJsonPath('data.1.id', $faq2->id)
            ->assertJsonPath('data.2.id', $faq3->id);
    }

    /** @test */
    public function authenticated_user_can_view_specific_faq()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->active()->create();

        $response = $this->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQ retrieved successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'order' => $faq->order,
                    'is_active' => $faq->is_active,
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_faq()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/faqs/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'FAQ not found'
            ]);
    }

    /** @test */
    public function it_returns_empty_array_when_no_active_faqs()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FAQs retrieved successfully',
                'data' => []
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_faqs()
    {
        $response = $this->getJson('/api/faqs');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_specific_faq()
    {
        $faq = FAQ::factory()->active()->create();

        $response = $this->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(401);
    }
}