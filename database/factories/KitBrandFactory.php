<?php

namespace Database\Factories;

use App\Models\KitBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KitBrand>
 */
class KitBrandFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
