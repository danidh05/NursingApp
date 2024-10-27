<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Nurse;

class NurseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Nurse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone_number' => $this->faker->unique()->phoneNumber(),
            'address' => $this->faker->address(),
            'profile_picture' => $this->faker->imageUrl(200, 200, 'nurse', true, 'Faker'), // Optional: mock URL for the profile picture
            'gender' => $this->faker->randomElement(['male', 'female']), // Nurse gender preference
        ];
    }
}