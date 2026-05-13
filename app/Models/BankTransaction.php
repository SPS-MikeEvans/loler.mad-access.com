<?php

namespace App\Models;

use Database\Factories\BankTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankTransaction extends Model
{
    /** @use HasFactory<BankTransactionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'bank_connection_id',
        'external_id',
        'booking_date',
        'value_date',
        'amount',
        'currency',
        'counterparty_name',
        'description',
        'raw_payload',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:2',
            'raw_payload' => 'array',
            'reconciled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<BankConnection, $this> */
    public function bankConnection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class);
    }

    /** @return HasMany<Reconciliation, $this> */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function isCredit(): bool
    {
        return (float) $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return (float) $this->amount < 0;
    }

    public function matchedAmount(): float
    {
        return (float) $this->reconciliations()->sum('matched_amount');
    }

    public function outstandingAmount(): float
    {
        return round(abs((float) $this->amount) - $this->matchedAmount(), 2);
    }

    /** @param Builder<BankTransaction> $query */
    public function scopeUnreconciled(Builder $query): void
    {
        $query->whereNull('reconciled_at');
    }
}
