<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\User;
use App\Models\Nurse;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition()
    {
        return [
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence,
            'user_id' => User::factory(),
            'nurse_id' => Nurse::factory(),
        ];
    }
}