<?php

namespace Tests\Feature;

use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FAQTranslationFixTest extends TestCase
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
    public function english_request_returns_original_content_when_no_english_translation_exists()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original English content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original English Question',
            'answer' => 'Original English Answer',
        ]);

        // Add only Arabic translation (no English translation)
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'Arabic Question',
            'answer' => 'Arabic Answer',
        ]);

        // Request English content
        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Should return original English content, NOT Arabic translation
        $this->assertEquals('Original English Question', $data[0]['question']);
        $this->assertEquals('Original English Answer', $data[0]['answer']);
        $this->assertNull($data[0]['translation']); // No translation object since no English translation exists
    }

    /** @test */
    public function english_request_returns_english_translation_when_available()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // Add English translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Translation Question',
            'answer' => 'English Translation Answer',
        ]);

        // Request English content
        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Should return English translation
        $this->assertEquals('English Translation Question', $data[0]['question']);
        $this->assertEquals('English Translation Answer', $data[0]['answer']);
        $this->assertEquals('en', $data[0]['translation']['locale']);
    }

    /** @test */
    public function arabic_request_returns_arabic_translation_when_available()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // Add Arabic translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'Arabic Question',
            'answer' => 'Arabic Answer',
        ]);

        // Request Arabic content
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Should return Arabic translation
        $this->assertEquals('Arabic Question', $data[0]['question']);
        $this->assertEquals('Arabic Answer', $data[0]['answer']);
        $this->assertEquals('ar', $data[0]['translation']['locale']);
    }

    /** @test */
    public function arabic_request_returns_original_content_when_no_arabic_translation_exists()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // Add only English translation (no Arabic translation)
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question',
            'answer' => 'English Answer',
        ]);

        // Request Arabic content
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Should return original content, NOT English translation
        $this->assertEquals('Original Question', $data[0]['question']);
        $this->assertEquals('Original Answer', $data[0]['answer']);
        $this->assertNull($data[0]['translation']); // No translation object since no Arabic translation exists
    }

    /** @test */
    public function no_accept_language_header_returns_original_content()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // Add Arabic translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'Arabic Question',
            'answer' => 'Arabic Answer',
        ]);

        // Request without Accept-Language header (defaults to English)
        $response = $this->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Should return original content since no English translation exists
        $this->assertEquals('Original Question', $data[0]['question']);
        $this->assertEquals('Original Answer', $data[0]['answer']);
        $this->assertNull($data[0]['translation']);
    }

    /** @test */
    public function specific_faq_endpoint_works_correctly_with_translations()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // Add English translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question',
            'answer' => 'English Answer',
        ]);

        // Request specific FAQ with English
        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should return English translation
        $this->assertEquals('English Question', $data['question']);
        $this->assertEquals('English Answer', $data['answer']);
        $this->assertEquals('en', $data['translation']['locale']);
    }

    /** @test */
    public function specific_faq_endpoint_returns_original_when_no_translation_exists()
    {
        Sanctum::actingAs($this->user);

        // Create FAQ with original content
        $faq = FAQ::factory()->active()->create([
            'question' => 'Original Question',
            'answer' => 'Original Answer',
        ]);

        // No translations added

        // Request specific FAQ with English
        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should return original content
        $this->assertEquals('Original Question', $data['question']);
        $this->assertEquals('Original Answer', $data['answer']);
        $this->assertNull($data['translation']);
    }

    /** @test */
    public function multiple_faqs_with_mixed_translations_work_correctly()
    {
        Sanctum::actingAs($this->user);

        // FAQ 1: Has English translation
        $faq1 = FAQ::factory()->active()->create([
            'question' => 'Original Question 1',
            'answer' => 'Original Answer 1',
            'order' => 1,
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq1->id,
            'locale' => 'en',
            'question' => 'English Question 1',
            'answer' => 'English Answer 1',
        ]);

        // FAQ 2: Has Arabic translation only
        $faq2 = FAQ::factory()->active()->create([
            'question' => 'Original Question 2',
            'answer' => 'Original Answer 2',
            'order' => 2,
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq2->id,
            'locale' => 'ar',
            'question' => 'Arabic Question 2',
            'answer' => 'Arabic Answer 2',
        ]);

        // FAQ 3: No translations
        $faq3 = FAQ::factory()->active()->create([
            'question' => 'Original Question 3',
            'answer' => 'Original Answer 3',
            'order' => 3,
        ]);

        // Request English content
        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // FAQ 1: Should return English translation
        $this->assertEquals('English Question 1', $data[0]['question']);
        $this->assertEquals('English Answer 1', $data[0]['answer']);
        $this->assertEquals('en', $data[0]['translation']['locale']);
        
        // FAQ 2: Should return original content (no English translation)
        $this->assertEquals('Original Question 2', $data[1]['question']);
        $this->assertEquals('Original Answer 2', $data[1]['answer']);
        $this->assertNull($data[1]['translation']);
        
        // FAQ 3: Should return original content (no translations)
        $this->assertEquals('Original Question 3', $data[2]['question']);
        $this->assertEquals('Original Answer 3', $data[2]['answer']);
        $this->assertNull($data[2]['translation']);
    }
}