<?php

namespace Database\Factories;

use App\Models\About;
use Illuminate\Database\Eloquent\Factories\Factory;

class AboutFactory extends Factory
{
    protected $model = About::class;

    public function definition()
    {
        return [
            'online_shop_url' => $this->faker->url,
            'facebook_url' => $this->faker->url,
            'instagram_url' => $this->faker->url,
            'whatsapp_number' => $this->faker->phoneNumber,
            'description' => $this->faker->paragraph,
        ];
    }
}