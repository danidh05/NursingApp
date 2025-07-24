<?php

namespace Database\Factories;

use App\Models\Popup;
use App\Models\User;
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
            'image' => $this->faker->imageUrl(800, 600, 'business'),
            'title' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement([Popup::TYPE_INFO, Popup::TYPE_WARNING, Popup::TYPE_PROMO]),
            'start_date' => null,
            'end_date' => null,
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'user_id' => null, // Global popup by default
        ];
    }

    /**
     * Indicate that the popup is for a specific user.
     */
    public function forUser(User $user): Factory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Indicate that the popup is a birthday popup.
     */
    public function birthday(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'title' => '🎉 Happy Birthday!',
                'content' => 'We hope you have a wonderful day filled with joy and happiness! 🎂',
                'type' => Popup::TYPE_BIRTHDAY,
                'start_date' => now()->startOfDay(),
                'end_date' => now()->endOfDay(),
            'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the popup is currently active.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'start_date' => now()->subHour(),
                'end_date' => now()->addHour(),
            ];
        });
    }

    /**
     * Indicate that the popup is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the popup is scheduled for the future.
     */
    public function scheduled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'start_date' => now()->addDay(),
                'end_date' => now()->addDays(2),
            'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the popup is expired.
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'start_date' => now()->subDays(2),
                'end_date' => now()->subDay(),
            'is_active' => true,
            ];
        });
    }
}
