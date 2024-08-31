<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Request;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Service;
use Illuminate\Support\Str;

class RequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Request::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Assuming there's a User factory
            'nurse_id' => Nurse::factory(), // Assuming there's a Nurse factory
            'service_id' => Service::factory(), // Assuming there's a Service factory
            'status' => $this->faker->randomElement(['pending', 'completed', 'canceled']), // Example statuses
            'scheduled_time' => $this->faker->dateTimeBetween('now', '+1 month'), // Random time in the future
            'location' => $this->faker->address, // Random address
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}