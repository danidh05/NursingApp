<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Popup>
 */
class PopupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => $this->faker->imageUrl(800, 600, 'medical', true, 'Healthcare'),
            'title' => $this->faker->randomElement([
                'New Service Available!',
                'Special Offer',
                'Important Health Update',
                'Welcome to Our App',
                'Emergency Services Available'
            ]),
            'content' => $this->faker->randomElement([
                'We are excited to announce our new home nursing services. Professional care delivered to your doorstep.',
                'Limited time offer: Get 20% off on your first nursing service booking. Book now!',
                'Stay updated with the latest health guidelines and safety measures for your wellbeing.',
                'Welcome to our healthcare app. Your trusted partner in health and wellness.',
                'Our emergency nursing services are now available 24/7. Contact us anytime for urgent care.'
            ]),
            'type' => $this->faker->randomElement(['info', 'warning', 'promo']),
            'start_date' => $this->faker->optional(0.6)->dateTimeBetween('-1 week', '+1 week'),
            'end_date' => $this->faker->optional(0.6)->dateTimeBetween('+1 week', '+1 month'),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Create an active popup (no date restrictions)
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => null,
            'end_date' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive popup
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a popup with specific type
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Create a popup that's currently active (within date range)
     */
    public function currentlyActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(7),
            'is_active' => true,
        ]);
    }

    /**
     * Create a popup that's expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(1),
            'is_active' => true,
        ]);
    }
}
