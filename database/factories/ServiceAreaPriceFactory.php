<?php

namespace Database\Factories;

use App\Models\ServiceAreaPrice;
use App\Models\Service;
use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAreaPrice>
 */
class ServiceAreaPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'area_id' => Area::factory(),
            'price' => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}