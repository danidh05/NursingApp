<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\User;
use App\Models\Area;
use App\Models\ServiceAreaPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnhancedMultilingualTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->user = User::factory()->create();
        $this->user->role_id = 2; // User role
        $this->user->save();
        
        $this->area = Area::factory()->create(['name' => 'Beirut']);
    }

    /** @test */
    public function services_return_english_content_when_accept_language_is_english()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service Name',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertCount(1, $data);
        $this->assertEquals('English Service Name', $data[0]['name']);
        $this->assertEquals('en', $data[0]['translation']['locale']);
    }

    /** @test */
    public function services_return_arabic_content_when_accept_language_is_arabic()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service Name',
        ]);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertCount(1, $data);
        $this->assertEquals('اسم الخدمة بالعربية', $data[0]['name']);
        $this->assertEquals('ar', $data[0]['translation']['locale']);
    }

    /** @test */
    public function services_fallback_to_english_when_arabic_not_available()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service Name',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertCount(1, $data);
        $this->assertEquals('English Service Name', $data[0]['name']); // Falls back to English
        $this->assertEquals('en', $data[0]['translation']['locale']);
    }

    /** @test */
    public function services_include_area_based_pricing_with_translations()
    {
        Sanctum::actingAs($this->user);
        
        // Set user's area
        $this->user->area_id = $this->area->id;
        $this->user->save();

        $service = Service::factory()->create(['name' => 'Default Service', 'price' => 100.00]);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);

        // Create area-specific pricing
        ServiceAreaPrice::factory()->create([
            'service_id' => $service->id,
            'area_id' => $this->area->id,
            'price' => 150.00,
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertCount(1, $data);
        $this->assertEquals('اسم الخدمة بالعربية', $data[0]['name']);
        $this->assertEquals(150.00, $data[0]['price']); // Area-specific price
        $this->assertEquals('Beirut', $data[0]['area_name']); // Area name
        $this->assertEquals('ar', $data[0]['translation']['locale']);
    }

    /** @test */
    public function faqs_return_english_content_when_accept_language_is_english()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->active()->create([
            'question' => 'Default Question',
            'answer' => 'Default Answer',
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question?',
            'answer' => 'English Answer',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('English Question?', $data[0]['question']);
        $this->assertEquals('English Answer', $data[0]['answer']);
        $this->assertEquals('en', $data[0]['translation']['locale']);
    }

    /** @test */
    public function faqs_return_arabic_content_when_accept_language_is_arabic()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->active()->create([
            'question' => 'Default Question',
            'answer' => 'Default Answer',
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question?',
            'answer' => 'English Answer',
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'سؤال بالعربية؟',
            'answer' => 'إجابة بالعربية',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('سؤال بالعربية؟', $data[0]['question']);
        $this->assertEquals('إجابة بالعربية', $data[0]['answer']);
        $this->assertEquals('ar', $data[0]['translation']['locale']);
    }

    /** @test */
    public function faqs_fallback_to_english_when_arabic_not_available()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->active()->create([
            'question' => 'Default Question',
            'answer' => 'Default Answer',
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question?',
            'answer' => 'English Answer',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/faqs');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('English Question?', $data[0]['question']); // Falls back to English
        $this->assertEquals('English Answer', $data[0]['answer']); // Falls back to English
        $this->assertEquals('en', $data[0]['translation']['locale']);
    }

    /** @test */
    public function specific_service_returns_translated_content()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson("/api/services/{$service->id}");

        $response->assertStatus(200);
        $data = $response->json('service');
        $this->assertEquals('اسم الخدمة بالعربية', $data['name']);
        $this->assertEquals('ar', $data['translation']['locale']);
    }

    /** @test */
    public function specific_faq_returns_translated_content()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->active()->create([
            'question' => 'Default Question',
            'answer' => 'Default Answer',
        ]);
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'سؤال بالعربية؟',
            'answer' => 'إجابة بالعربية',
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson("/api/faqs/{$faq->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('سؤال بالعربية؟', $data['question']);
        $this->assertEquals('إجابة بالعربية', $data['answer']);
        $this->assertEquals('ar', $data['translation']['locale']);
    }

    /** @test */
    public function middleware_handles_complex_accept_language_header()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);

        // Complex Accept-Language header with quality values
        $response = $this->withHeaders(['Accept-Language' => 'ar,en;q=0.9,en-US;q=0.8'])
            ->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertEquals('اسم الخدمة بالعربية', $data[0]['name']);
    }

    /** @test */
    public function middleware_defaults_to_english_when_no_accept_language_header()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create(['name' => 'Default Service']);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
        $data = $response->json('services');
        $this->assertEquals('Default Service', $data[0]['name']); // Uses default name
    }

    /** @test */
    public function unauthenticated_user_cannot_access_translated_endpoints()
    {
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/services');

        $response->assertStatus(401);
    }
} 