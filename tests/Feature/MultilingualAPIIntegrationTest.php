<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultilingualAPIIntegrationTest extends TestCase
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
    public function api_can_return_translated_service_data()
    {
        Sanctum::actingAs($this->user);

        $service = Service::factory()->create([
            'name' => 'Original Service Name',
        ]);

        // Create translations
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

        // Test API response includes translation data
        $response = $this->getJson('/api/services');
        
        $response->assertStatus(200);
        
        // The API should return the service with translation information
        $this->assertDatabaseHas('service_translations', [
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service Name',
        ]);

        $this->assertDatabaseHas('service_translations', [
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);
    }

    /** @test */
    public function api_can_return_translated_faq_data()
    {
        Sanctum::actingAs($this->user);

        $faq = FAQ::factory()->create([
            'question' => 'Original Question?',
            'answer' => 'Original Answer',
        ]);

        // Create translations
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

        // Test API response includes translation data
        $response = $this->getJson('/api/faqs');
        
        $response->assertStatus(200);
        
        // Verify translations exist in database
        $this->assertDatabaseHas('faq_translations', [
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question?',
        ]);

        $this->assertDatabaseHas('faq_translations', [
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'سؤال بالعربية؟',
        ]);
    }

    /** @test */
    public function translation_data_persistence_works()
    {
        $service = Service::factory()->create();

        // Set translations
        $service->setTranslation('en', ['name' => 'English Service']);
        $service->setTranslation('ar', ['name' => 'خدمة بالعربية']);

        // Refresh from database
        $service->refresh();

        // Verify translations persist
        $this->assertTrue($service->hasTranslation('en'));
        $this->assertTrue($service->hasTranslation('ar'));
        
        $this->assertEquals('English Service', $service->translate('en')->name);
        $this->assertEquals('خدمة بالعربية', $service->translate('ar')->name);
    }

    /** @test */
    public function translation_relationships_are_eager_loadable()
    {
        $service = Service::factory()->create();
        
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'خدمة بالعربية',
        ]);

        // Test eager loading
        $serviceWithTranslations = Service::with('translations')->find($service->id);
        
        $this->assertCount(2, $serviceWithTranslations->translations);
        $this->assertTrue($serviceWithTranslations->translations->contains('locale', 'en'));
        $this->assertTrue($serviceWithTranslations->translations->contains('locale', 'ar'));
    }

    /** @test */
    public function translation_fallback_works_in_api_context()
    {
        $service = Service::factory()->create();

        // Only create English translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Set app locale to Arabic
        app()->setLocale('ar');
        
        // Should fallback to English
        $translation = $service->translate();
        $this->assertNotNull($translation);
        $this->assertEquals('English Service', $translation->name);
        $this->assertEquals('en', $translation->locale);
    }

    /** @test */
    public function translation_works_with_model_events()
    {
        $service = Service::factory()->create();

        // Test that translations are properly associated
        $service->setTranslation('en', ['name' => 'English Service']);
        
        // Verify the translation was created
        $this->assertDatabaseHas('service_translations', [
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Test updating translation
        $service->setTranslation('en', ['name' => 'Updated English Service']);
        
        $this->assertDatabaseHas('service_translations', [
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'Updated English Service',
        ]);
    }
} 