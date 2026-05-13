<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issued = fake()->dateTimeBetween('-90 days', 'now');
        $issuedAt = $issued->format('Y-m-d');
        $periodFrom = fake()->dateTimeBetween('-120 days', $issued)->format('Y-m-d');
        $subtotal = fake()->randomFloat(2, 50, 1500);

        return [
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-'.now()->year.'-'.Str::upper(Str::random(6)),
            'issued_date' => $issuedAt,
            'due_date' => (new \DateTimeImmutable($issuedAt))->modify('+14 days')->format('Y-m-d'),
            'period_from' => $periodFrom,
            'period_to' => $issuedAt,
            'notes' => null,
            'subtotal' => $subtotal,
            'discount_percent' => null,
            'total_amount' => $subtotal,
            'status' => InvoiceStatus::Sent->value,
            'sent_at' => $issuedAt,
            'paid_at' => null,
            'last_chase_sent_at' => null,
        ];
    }

    public function paid(): self
    {
        return $this->state(['status' => InvoiceStatus::Paid->value, 'paid_at' => now()]);
    }

    public function overdue(): self
    {
        return $this->state([
            'status' => InvoiceStatus::Overdue->value,
            'due_date' => now()->subDays(20)->toDateString(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(['status' => InvoiceStatus::Cancelled->value]);
    }
}
