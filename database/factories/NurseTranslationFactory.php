<?php

namespace Database\Factories;

use App\Models\Nurse;
use App\Models\NurseTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NurseTranslation>
 */
class NurseTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NurseTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nurse_id' => Nurse::factory(),
            'locale' => 'en', // Default to English to avoid conflicts
            'name' => $this->faker->name(),
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
     * Create translation for a specific nurse.
     */
    public function forNurse(Nurse $nurse): static
    {
        return $this->state(fn (array $attributes) => [
            'nurse_id' => $nurse->id,
        ]);
    }

    /**
     * Create translation for a specific nurse and locale.
     */
    public function forNurseAndLocale(Nurse $nurse, string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'nurse_id' => $nurse->id,
            'locale' => $locale,
        ]);
    }
} 