<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedMultilingualTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TranslationService $translationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        
        $this->admin = User::factory()->create();
        $this->admin->role_id = 1; // Admin role
        $this->admin->save();

        $this->translationService = app(TranslationService::class);
    }

    /** @test */
    public function translation_service_works_correctly()
    {
        $service = Service::factory()->create();

        // Test setting multiple translations
        $this->translationService->setTranslations($service, [
            'en' => ['name' => 'English Service'],
            'ar' => ['name' => 'خدمة بالعربية'],
        ]);

        $this->assertTrue($this->translationService->hasTranslation($service, 'en'));
        $this->assertTrue($this->translationService->hasTranslation($service, 'ar'));

        // Test getting translated data
        $englishData = $this->translationService->getTranslatedData($service, 'en');
        $arabicData = $this->translationService->getTranslatedData($service, 'ar');

        $this->assertEquals('English Service', $englishData['name']);
        $this->assertEquals('خدمة بالعربية', $arabicData['name']);

        // Test getting all translations
        $allTranslations = $this->translationService->getAllTranslations($service);
        $this->assertArrayHasKey('en', $allTranslations);
        $this->assertArrayHasKey('ar', $allTranslations);
    }

    /** @test */
    public function translation_validation_works()
    {
        $invalidData = [
            'invalid_locale' => ['name' => 'Invalid Service'],
            'en' => [], // Missing required field
        ];

        $errors = $this->translationService->validateTranslationData($invalidData, ['name']);
        
        $this->assertContains("Invalid locale format: invalid_locale", $errors);
        $this->assertContains("Missing required field 'name' for locale 'en'", $errors);
    }

    /** @test */
    public function translation_deletion_works()
    {
        $service = Service::factory()->create();
        
        $service->setTranslation('en', ['name' => 'English Service']);
        $service->setTranslation('ar', ['name' => 'خدمة بالعربية']);

        $this->assertTrue($service->hasTranslation('en'));
        $this->assertTrue($service->hasTranslation('ar'));

        // Delete Arabic translation
        $this->translationService->deleteTranslation($service, 'ar');
        
        $this->assertTrue($service->hasTranslation('en'));
        $this->assertFalse($service->hasTranslation('ar'));
    }

    /** @test */
    public function translation_with_complex_content_works()
    {
        $faq = FAQ::factory()->create();

        $faq->setTranslation('en', [
            'question' => 'What services do you offer?',
            'answer' => 'We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance.',
        ]);

        $faq->setTranslation('ar', [
            'question' => 'ما هي الخدمات التي تقدمونها؟',
            'answer' => 'نقدم مجموعة واسعة من خدمات التمريض بما في ذلك الرعاية المنزلية ورعاية المسنين والمساعدة الطبية المتخصصة.',
        ]);

        $englishTranslation = $faq->translate('en');
        $arabicTranslation = $faq->translate('ar');

        $this->assertEquals('What services do you offer?', $englishTranslation->question);
        $this->assertStringContainsString('nursing services', $englishTranslation->answer);
        $this->assertEquals('ما هي الخدمات التي تقدمونها؟', $arabicTranslation->question);
        $this->assertStringContainsString('خدمات التمريض', $arabicTranslation->answer);
    }

    /** @test */
    public function translation_cascade_delete_works()
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

        // Verify translations exist
        $this->assertCount(2, $service->translations);

        // Delete the service
        $service->delete();

        // Verify translations are cascade deleted
        $this->assertDatabaseMissing('service_translations', [
            'service_id' => $service->id,
        ]);
    }

    /** @test */
    public function translation_unique_constraint_works()
    {
        $service = Service::factory()->create();

        // Create first translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Try to create duplicate translation (should fail)
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Create another translation with same service_id and locale
        ServiceTranslation::create([
            'service_id' => $service->id,
            'locale' => 'en', // Same locale
            'name' => 'Another English Service',
        ]);
    }

    /** @test */
    public function translation_with_app_locale_fallback()
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
    public function translation_with_null_locale_returns_null()
    {
        $service = Service::factory()->create();
        
        // No translations exist
        $translation = $service->translate('fr');
        $this->assertNull($translation);
        
        // Also test with a locale that doesn't exist
        $translation2 = $service->translate('de');
        $this->assertNull($translation2);
    }

    /** @test */
    public function translation_works_with_different_model_types()
    {
        // Test Service
        $service = Service::factory()->create();
        $service->setTranslation('en', ['name' => 'Service Name']);
        
        $this->assertTrue($service->hasTranslation('en'));
        $this->assertEquals('Service Name', $service->translate('en')->name);

        // Test FAQ
        $faq = FAQ::factory()->create();
        $faq->setTranslation('en', ['question' => 'Test Question', 'answer' => 'Test Answer']);
        
        $this->assertTrue($faq->hasTranslation('en'));
        $translation = $faq->translate('en');
        $this->assertEquals('Test Question', $translation->question);
        $this->assertEquals('Test Answer', $translation->answer);
    }
} 