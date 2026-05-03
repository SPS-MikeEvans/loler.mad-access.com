<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\KitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KitGroup>
 */
class KitGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
