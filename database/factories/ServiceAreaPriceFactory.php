<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Area;
use App\Models\ServiceAreaPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAreaPrice>
 */
class ServiceAreaPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServiceAreaPrice::class;

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
            'price' => $this->faker->randomFloat(2, 50, 200),
        ];
    }

    /**
     * Create service area price for specific service and area.
     */
    public function forServiceAndArea(Service $service, Area $area): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => $service->id,
            'area_id' => $area->id,
        ]);
    }
}