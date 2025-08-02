<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\FAQ;
use App\Models\FAQTranslation;
use App\Models\Slider;
use App\Models\SliderTranslation;
use App\Models\Popup;
use App\Models\PopupTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultilingualSupportTest extends TestCase
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
    public function service_can_have_translations()
    {
        $service = Service::factory()->create([
            'name' => 'English Service Name',
        ]);

        // Create English translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
            'name' => 'English Service Name',
        ]);

        // Create Arabic translation
        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
            'name' => 'اسم الخدمة بالعربية',
        ]);

        // Test English translation
        $englishTranslation = $service->translate('en');
        $this->assertNotNull($englishTranslation);
        $this->assertEquals('English Service Name', $englishTranslation->name);

        // Test Arabic translation
        $arabicTranslation = $service->translate('ar');
        $this->assertNotNull($arabicTranslation);
        $this->assertEquals('اسم الخدمة بالعربية', $arabicTranslation->name);

        // Test fallback to English when Arabic doesn't exist
        $service2 = Service::factory()->create();
        ServiceTranslation::factory()->create([
            'service_id' => $service2->id,
            'locale' => 'en',
            'name' => 'English Only Service',
        ]);

        $fallbackTranslation = $service2->translate('ar');
        $this->assertNotNull($fallbackTranslation);
        $this->assertEquals('English Only Service', $fallbackTranslation->name);
    }

    /** @test */
    public function faq_can_have_translations()
    {
        $faq = FAQ::factory()->create([
            'question' => 'English Question?',
            'answer' => 'English Answer',
        ]);

        // Create English translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'en',
            'question' => 'English Question?',
            'answer' => 'English Answer',
        ]);

        // Create Arabic translation
        FAQTranslation::factory()->create([
            'faq_id' => $faq->id,
            'locale' => 'ar',
            'question' => 'سؤال بالعربية؟',
            'answer' => 'إجابة بالعربية',
        ]);

        // Test English translation
        $englishTranslation = $faq->translate('en');
        $this->assertNotNull($englishTranslation);
        $this->assertEquals('English Question?', $englishTranslation->question);
        $this->assertEquals('English Answer', $englishTranslation->answer);

        // Test Arabic translation
        $arabicTranslation = $faq->translate('ar');
        $this->assertNotNull($arabicTranslation);
        $this->assertEquals('سؤال بالعربية؟', $arabicTranslation->question);
        $this->assertEquals('إجابة بالعربية', $arabicTranslation->answer);
    }

    /** @test */
    public function slider_can_have_translations()
    {
        $slider = Slider::factory()->create([
            'title' => 'English Title',
            'subtitle' => 'English Subtitle',
        ]);

        // Create English translation
        SliderTranslation::factory()->create([
            'slider_id' => $slider->id,
            'locale' => 'en',
            'title' => 'English Title',
            'subtitle' => 'English Subtitle',
        ]);

        // Create Arabic translation
        SliderTranslation::factory()->create([
            'slider_id' => $slider->id,
            'locale' => 'ar',
            'title' => 'عنوان بالعربية',
            'subtitle' => 'عنوان فرعي بالعربية',
        ]);

        // Test English translation
        $englishTranslation = $slider->translate('en');
        $this->assertNotNull($englishTranslation);
        $this->assertEquals('English Title', $englishTranslation->title);
        $this->assertEquals('English Subtitle', $englishTranslation->subtitle);

        // Test Arabic translation
        $arabicTranslation = $slider->translate('ar');
        $this->assertNotNull($arabicTranslation);
        $this->assertEquals('عنوان بالعربية', $arabicTranslation->title);
        $this->assertEquals('عنوان فرعي بالعربية', $arabicTranslation->subtitle);
    }

    /** @test */
    public function popup_can_have_translations()
    {
        $popup = Popup::factory()->create([
            'title' => 'English Title',
            'content' => 'English Content',
        ]);

        // Create English translation
        PopupTranslation::factory()->create([
            'popup_id' => $popup->id,
            'locale' => 'en',
            'title' => 'English Title',
            'content' => 'English Content',
        ]);

        // Create Arabic translation
        PopupTranslation::factory()->create([
            'popup_id' => $popup->id,
            'locale' => 'ar',
            'title' => 'عنوان بالعربية',
            'content' => 'محتوى بالعربية',
        ]);

        // Test English translation
        $englishTranslation = $popup->translate('en');
        $this->assertNotNull($englishTranslation);
        $this->assertEquals('English Title', $englishTranslation->title);
        $this->assertEquals('English Content', $englishTranslation->content);

        // Test Arabic translation
        $arabicTranslation = $popup->translate('ar');
        $this->assertNotNull($arabicTranslation);
        $this->assertEquals('عنوان بالعربية', $arabicTranslation->title);
        $this->assertEquals('محتوى بالعربية', $arabicTranslation->content);
    }

    /** @test */
    public function translation_fallback_works_correctly()
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
        $this->assertEquals('en', $translation->locale);
    }

    /** @test */
    public function can_set_translations_using_trait_method()
    {
        $service = Service::factory()->create();

        // Set English translation
        $service->setTranslation('en', ['name' => 'English Service']);
        
        // Set Arabic translation
        $service->setTranslation('ar', ['name' => 'خدمة بالعربية']);

        // Verify translations were created
        $this->assertTrue($service->hasTranslation('en'));
        $this->assertTrue($service->hasTranslation('ar'));
        
        $this->assertEquals('English Service', $service->translate('en')->name);
        $this->assertEquals('خدمة بالعربية', $service->translate('ar')->name);
    }

    /** @test */
    public function can_get_available_locales()
    {
        $service = Service::factory()->create();

        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'en',
        ]);

        ServiceTranslation::factory()->create([
            'service_id' => $service->id,
            'locale' => 'ar',
        ]);

        $availableLocales = $service->getAvailableLocales();
        $this->assertContains('en', $availableLocales);
        $this->assertContains('ar', $availableLocales);
        $this->assertCount(2, $availableLocales);
    }

    /** @test */
    public function translation_relationships_work_correctly()
    {
        $service = Service::factory()->create();
        
        // Create translations with different locales
        ServiceTranslation::factory()->english()->create([
            'service_id' => $service->id,
        ]);

        ServiceTranslation::factory()->arabic()->create([
            'service_id' => $service->id,
        ]);

        $this->assertCount(2, $service->translations);
        $this->assertInstanceOf(ServiceTranslation::class, $service->translations->first());
    }

    /** @test */
    public function can_update_existing_translation()
    {
        $service = Service::factory()->create();
        
        // Create initial translation
        $service->setTranslation('en', ['name' => 'Original Name']);
        
        // Update the translation
        $service->setTranslation('en', ['name' => 'Updated Name']);
        
        $translation = $service->translate('en');
        $this->assertEquals('Updated Name', $translation->name);
        
        // Should still have only one translation record
        $this->assertCount(1, $service->translations);
    }
} 