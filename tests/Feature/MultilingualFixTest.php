<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultilingualFixTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function translation_service_works_without_protected_methods()
    {
        $service = Service::factory()->create();
        $translationService = app(TranslationService::class);

        // Create translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Test that TranslationService can get translated data
        $translatedData = $translationService->getTranslatedData($service, 'en');
        
        $this->assertArrayHasKey('name', $translatedData);
        $this->assertEquals('English Service', $translatedData['name']);
    }

    /** @test */
    public function fallback_works_when_translation_exists()
    {
        $service = Service::factory()->create();
        
        // Create English translation
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
    public function returns_null_when_no_translations_exist()
    {
        $service = Service::factory()->create();
        
        // No translations exist
        $translation = $service->translate('fr');
        $this->assertNull($translation);
    }

    /** @test */
    public function unique_constraint_prevents_duplicates()
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
        
        ServiceTranslation::create([
            'service_id' => $service->id,
            'locale' => 'en', // Same locale
            'name' => 'Another English Service',
        ]);
    }
} 