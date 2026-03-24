<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by_user_id' => User::factory()->state(['role' => 'admin']),
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'drop_off_at' => now(),
            'drop_off_signed_by' => fake()->name(),
            'drop_off_ip' => fake()->ipv4(),
        ]);
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'complete',
            'drop_off_at' => now()->subDays(3),
            'drop_off_signed_by' => fake()->name(),
            'drop_off_ip' => fake()->ipv4(),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'returned',
            'drop_off_at' => now()->subDays(7),
            'drop_off_signed_by' => fake()->name(),
            'drop_off_ip' => fake()->ipv4(),
            'return_at' => now(),
            'return_signed_by' => fake()->name(),
            'return_ip' => fake()->ipv4(),
        ]);
    }
}
