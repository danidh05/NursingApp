<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settings>
 */
class SettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['string', 'url', 'phone', 'email', 'number', 'boolean']),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the setting is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the setting is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a job application URL setting.
     */
    public function jobApplicationUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'job_application_url',
            'value' => $this->faker->url(),
            'type' => 'url',
            'description' => 'URL for job application redirect',
            'is_active' => true,
        ]);
    }

    /**
     * Create a WhatsApp support number setting.
     */
    public function whatsappSupportNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'whatsapp_support_number',
            'value' => $this->faker->phoneNumber(),
            'type' => 'phone',
            'description' => 'WhatsApp number for customer support',
            'is_active' => true,
        ]);
    }
}