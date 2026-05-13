<?php

namespace Database\Factories;

use App\Models\BankConnection;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $booking = fake()->dateTimeBetween('-60 days', 'now');

        return [
            'bank_connection_id' => BankConnection::factory(),
            'external_id' => (string) Str::uuid(),
            'booking_date' => $booking->format('Y-m-d'),
            'value_date' => $booking->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, -500, 1500),
            'currency' => 'GBP',
            'counterparty_name' => fake()->company(),
            'description' => fake()->sentence(),
            'raw_payload' => ['source' => 'factory'],
            'reconciled_at' => null,
        ];
    }

    public function credit(?float $amount = null): self
    {
        return $this->state(['amount' => $amount ?? fake()->randomFloat(2, 10, 1500)]);
    }

    public function debit(?float $amount = null): self
    {
        return $this->state(['amount' => -1 * ($amount ?? fake()->randomFloat(2, 10, 500))]);
    }

    public function reconciled(): self
    {
        return $this->state(['reconciled_at' => now()]);
    }
}
