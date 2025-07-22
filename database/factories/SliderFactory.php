<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slider>
 */
class SliderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => $this->faker->imageUrl(1200, 600, 'medical', true, 'Healthcare'),
            'title' => $this->faker->randomElement([
                'Professional Nursing Care',
                'Home Healthcare Services',
                'Expert Medical Assistance',
                '24/7 Nursing Support',
                'Quality Healthcare at Home'
            ]),
            'subtitle' => $this->faker->randomElement([
                'Trusted by thousands of families',
                'Professional and compassionate care',
                'Your health, our priority',
                'Expert care in the comfort of your home',
                'Reliable healthcare solutions'
            ]),
            'position' => $this->faker->numberBetween(1, 10),
            'link' => $this->faker->optional(0.7)->url(),
        ];
    }

    /**
     * Create a slider with no link
     */
    public function withoutLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => null,
        ]);
    }

    /**
     * Create a slider with a specific position
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
