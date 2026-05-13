<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'expense_category_id' => ExpenseCategory::factory(),
            'supplier' => fake()->company(),
            'amount' => fake()->randomFloat(2, 5, 500),
            'notes' => null,
            'receipt_path' => null,
            'reconciled_at' => null,
            'bank_transaction_id' => null,
        ];
    }

    public function reconciled(): self
    {
        return $this->state(['reconciled_at' => now()]);
    }
}
