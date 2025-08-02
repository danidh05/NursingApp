<?php

namespace Database\Factories;

use App\Models\Slider;
use App\Models\SliderTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SliderTranslation>
 */
class SliderTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SliderTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slider_id' => Slider::factory(),
            'locale' => 'en', // Default to English to avoid conflicts
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(6),
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
     * Create translation for a specific slider.
     */
    public function forSlider(Slider $slider): static
    {
        return $this->state(fn (array $attributes) => [
            'slider_id' => $slider->id,
        ]);
    }

    /**
     * Create translation for a specific slider and locale.
     */
    public function forSliderAndLocale(Slider $slider, string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'slider_id' => $slider->id,
            'locale' => $locale,
        ]);
    }
} 