<?php

namespace Database\Factories;

use App\Models\BankConnection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BankConnection>
 */
class BankConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'gocardless',
            'institution_id' => 'TIDE_TIDEGB22',
            'institution_name' => 'Tide',
            'requisition_id' => (string) Str::uuid(),
            'requisition_reference' => Str::random(40),
            'agreement_id' => (string) Str::uuid(),
            'account_ids' => [(string) Str::uuid()],
            'status' => BankConnection::STATUS_LINKED,
            'linked_at' => now(),
            'expires_at' => now()->addDays(90),
            'last_synced_at' => null,
            'created_by_user_id' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state([
            'status' => BankConnection::STATUS_PENDING,
            'linked_at' => null,
            'expires_at' => null,
            'account_ids' => null,
        ]);
    }

    public function expired(): self
    {
        return $this->state([
            'status' => BankConnection::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }
}
