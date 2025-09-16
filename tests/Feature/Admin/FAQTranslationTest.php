<?php

namespace Tests\Feature\Admin;

use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FAQTranslationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $faq;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create a test FAQ
        $this->faq = FAQ::create([
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.',
            'order' => 1,
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_get_faq_translations()
    {
        // Create some translations
        FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);

        FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'en',
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.'
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/faqs/{$this->faq->id}/translations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'locale',
                        'question',
                        'answer',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Translations retrieved successfully'
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function admin_can_add_translation_to_faq()
    {
        $translationData = [
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", $translationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'locale',
                    'question',
                    'answer',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Translation added successfully',
                'data' => [
                    'locale' => 'ar',
                    'question' => 'ما هي الخدمات التي تقدمونها؟',
                    'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
                ]
            ]);

        $this->assertDatabaseHas('faq_translations', [
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);
    }

    /** @test */
    public function admin_can_update_existing_translation()
    {
        // Create initial translation
        $translation = FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);

        $updatedData = [
            'question' => 'ما هي الخدمات المتاحة لديكم؟',
            'answer' => 'نقدم خدمات تمريض متخصصة ومتنوعة'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/faqs/{$this->faq->id}/translations/ar", $updatedData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'locale',
                    'question',
                    'answer',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Translation updated successfully',
                'data' => [
                    'locale' => 'ar',
                    'question' => 'ما هي الخدمات المتاحة لديكم؟',
                    'answer' => 'نقدم خدمات تمريض متخصصة ومتنوعة'
                ]
            ]);

        $this->assertDatabaseHas('faq_translations', [
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات المتاحة لديكم؟',
            'answer' => 'نقدم خدمات تمريض متخصصة ومتنوعة'
        ]);
    }

    /** @test */
    public function admin_can_delete_translation()
    {
        // Create translation
        $translation = FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/faqs/{$this->faq->id}/translations/ar");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Translation deleted successfully'
            ]);

        $this->assertDatabaseMissing('faq_translations', [
            'faq_id' => $this->faq->id,
            'locale' => 'ar'
        ]);
    }

    /** @test */
    public function admin_cannot_add_duplicate_translation()
    {
        // Create initial translation
        FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);

        $duplicateData = [
            'locale' => 'ar',
            'question' => 'سؤال مختلف',
            'answer' => 'إجابة مختلفة'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", $duplicateData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Translation for this locale already exists. Use update instead.'
            ]);
    }

    /** @test */
    public function admin_faq_list_shows_translation_status()
    {
        // Create translations
        FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'ar',
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
        ]);

        FAQTranslation::create([
            'faq_id' => $this->faq->id,
            'locale' => 'en',
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.'
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/faqs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'question',
                        'answer',
                        'order',
                        'is_active',
                        'available_translations',
                        'translation_count',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $faqData = $response->json('data')[0];
        $this->assertContains('ar', $faqData['available_translations']);
        $this->assertContains('en', $faqData['available_translations']);
        $this->assertEquals(2, $faqData['translation_count']);
    }

    /** @test */
    public function translation_validation_works_correctly()
    {
        // Test missing required fields
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale', 'question', 'answer']);

        // Test invalid locale
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", [
                'locale' => 'invalid',
                'question' => 'Test question',
                'answer' => 'Test answer'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);

        // Test question too long
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", [
                'locale' => 'ar',
                'question' => str_repeat('a', 256), // Too long
                'answer' => 'Test answer'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }

    /** @test */
    public function returns_404_for_nonexistent_faq()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/faqs/999/translations');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'FAQ not found'
            ]);
    }

    /** @test */
    public function returns_404_for_nonexistent_translation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/faqs/{$this->faq->id}/translations/ar", [
                'question' => 'Test',
                'answer' => 'Test'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Translation not found'
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_translation_endpoints()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/admin/faqs/{$this->faq->id}/translations");

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_translation_endpoints()
    {
        $response = $this->getJson("/api/admin/faqs/{$this->faq->id}/translations");

        $response->assertStatus(401);
    }

    /** @test */
    public function translation_endpoints_work_with_english_locale()
    {
        $translationData = [
            'locale' => 'en',
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services.'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", $translationData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'locale' => 'en'
                ]
            ]);
    }

    /** @test */
    public function translation_count_increases_after_adding_translation()
    {
        // Initial count should be 0
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/faqs');
        
        $initialCount = $response->json('data')[0]['translation_count'];
        $this->assertEquals(0, $initialCount);

        // Add translation
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/faqs/{$this->faq->id}/translations", [
                'locale' => 'ar',
                'question' => 'ما هي الخدمات التي تقدمونها؟',
                'answer' => 'نقدم مجموعة واسعة من خدمات التمريض'
            ]);

        // Count should be 1
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/faqs');
        
        $newCount = $response->json('data')[0]['translation_count'];
        $this->assertEquals(1, $newCount);
    }
}