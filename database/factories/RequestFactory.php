<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Request;
use App\Models\User;
use App\Models\Nurse;

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
            'user_id' => User::factory(), // Create associated User
            'nurse_id' => Nurse::factory(), // Create associated Nurse
            // Remove 'service_id' as services are handled via pivot table
            'status' => $this->faker->randomElement(['pending', 'completed', 'canceled']), // Example statuses
            'scheduled_time' => $this->faker->dateTimeBetween('now', '+1 month'), // Random time in the future
            'location' => $this->faker->address, // Random address
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}