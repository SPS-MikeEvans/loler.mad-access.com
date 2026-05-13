<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Fuel', 'Tools', 'Subsistence', 'Office', 'Software', 'Training', 'Travel', 'Phone']).' '.fake()->unique()->randomNumber(4),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
