<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'expense_date',
        'expense_category_id',
        'supplier',
        'amount',
        'notes',
        'receipt_path',
        'reconciled_at',
        'bank_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'reconciled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ExpenseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** @return BelongsTo<BankTransaction, $this> */
    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    /** @return MorphMany<Reconciliation, $this> */
    public function reconciliations(): MorphMany
    {
        return $this->morphMany(Reconciliation::class, 'matchable');
    }

    public function isReconciled(): bool
    {
        return $this->reconciled_at !== null;
    }

    /** @param Builder<Expense> $query */
    public function scopeUnreconciled(Builder $query): void
    {
        $query->whereNull('reconciled_at');
    }
}
