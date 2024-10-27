<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Request as NurseRequest;
use App\Models\User;
use App\Models\Nurse;

class RequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NurseRequest::class;

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
            'status' => $this->faker->randomElement(['pending', 'approved', 'completed', 'canceled']), // Example statuses
            'scheduled_time' => $this->faker->dateTimeBetween('now', '+1 month'), // Random future time
            'ending_time' => $this->faker->optional()->dateTimeBetween('now', '+2 months'), // Optional ending time
            'location' => $this->faker->address, // Random address
            'time_type' => $this->faker->randomElement(['full-time', 'part-time']), // Time type for the request
            'problem_description' => $this->faker->optional()->sentence(), // Optional problem description
            'nurse_gender' => $this->faker->randomElement(['male', 'female']), // Nurse gender preference
            'full_name' => $this->faker->name(), // Full name of the patient/user
            'phone_number' => $this->faker->phoneNumber(), // Contact phone number
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null, // By default, requests are not soft deleted
        ];
    }

    /**
     * Indicate that the request is soft deleted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deleted(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}