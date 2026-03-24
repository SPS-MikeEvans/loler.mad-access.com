<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
            'address' => fake()->address(),
            'contact_email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'notes' => null,
        ];
    }
}
