<?php

namespace Database\Factories;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reconciliation>
 */
class ReconciliationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_transaction_id' => BankTransaction::factory(),
            'matchable_type' => Reconciliation::TYPE_INVOICE,
            'matchable_id' => Invoice::factory(),
            'matched_amount' => fake()->randomFloat(2, 10, 500),
            'matched_by_user_id' => null,
            'notes' => null,
        ];
    }

    public function forExpense(?Expense $expense = null): self
    {
        return $this->state(function (array $attrs) use ($expense) {
            $target = $expense ?? Expense::factory()->create();

            return [
                'matchable_type' => Reconciliation::TYPE_EXPENSE,
                'matchable_id' => $target->id,
            ];
        });
    }
}
