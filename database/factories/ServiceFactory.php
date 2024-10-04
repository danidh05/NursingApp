<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 100),
            'discount_price' => $this->faker->optional()->randomFloat(2, 5, 80),
            // 'category_id' => \App\Models\Category::factory(), // Ensure you have a CategoryFactory
        ];
    }
}