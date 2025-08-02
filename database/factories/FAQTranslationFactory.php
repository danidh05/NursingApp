<?php

namespace Database\Factories;

use App\Models\FAQ;
use App\Models\FAQTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FAQTranslation>
 */
class FAQTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FAQTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faq_id' => FAQ::factory(),
            'locale' => 'en', // Default to English to avoid conflicts
            'question' => $this->faker->sentence() . '?',
            'answer' => $this->faker->paragraph(),
        ];
    }

    /**
     * Indicate that the translation is in English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'en',
        ]);
    }

    /**
     * Indicate that the translation is in Arabic.
     */
    public function arabic(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'ar',
        ]);
    }

    /**
     * Create translation for a specific FAQ.
     */
    public function forFAQ(FAQ $faq): static
    {
        return $this->state(fn (array $attributes) => [
            'faq_id' => $faq->id,
        ]);
    }

    /**
     * Create translation for a specific FAQ and locale.
     */
    public function forFAQAndLocale(FAQ $faq, string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'faq_id' => $faq->id,
            'locale' => $locale,
        ]);
    }
} 