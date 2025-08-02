<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceTranslation>
 */
class ServiceTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServiceTranslation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'locale' => 'en', // Default to English to avoid conflicts
            'name' => $this->faker->sentence(3),
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
     * Create translation for a specific service.
     */
    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => $service->id,
        ]);
    }

    /**
     * Create translation for a specific service and locale.
     */
    public function forServiceAndLocale(Service $service, string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => $service->id,
            'locale' => $locale,
        ]);
    }
} 