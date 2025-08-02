<?php

namespace Database\Factories;

use App\Models\Popup;
use App\Models\PopupTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PopupTranslation>
 */
class PopupTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PopupTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'popup_id' => Popup::factory(),
            'locale' => 'en', // Default to English to avoid conflicts
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph(),
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
     * Create translation for a specific popup.
     */
    public function forPopup(Popup $popup): static
    {
        return $this->state(fn (array $attributes) => [
            'popup_id' => $popup->id,
        ]);
    }

    /**
     * Create translation for a specific popup and locale.
     */
    public function forPopupAndLocale(Popup $popup, string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'popup_id' => $popup->id,
            'locale' => $locale,
        ]);
    }
} 