<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleTranslationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function service_can_have_translations()
    {
        $service = Service::factory()->create();

        // Create English translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Create Arabic translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'خدمة بالعربية',
        ]);

        // Test English translation
        $englishTranslation = $service->translate('en');
        $this->assertNotNull($englishTranslation);
        $this->assertEquals('English Service', $englishTranslation->name);

        // Test Arabic translation
        $arabicTranslation = $service->translate('ar');
        $this->assertNotNull($arabicTranslation);
        $this->assertEquals('خدمة بالعربية', $arabicTranslation->name);
    }

    /** @test */
    public function translation_fallback_works()
    {
        $service = Service::factory()->create();

        // Only create English translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service',
        ]);

        // Request Arabic translation should fallback to English
        $translation = $service->translate('ar');
        $this->assertNotNull($translation);
        $this->assertEquals('English Service', $translation->name);
    }
} 