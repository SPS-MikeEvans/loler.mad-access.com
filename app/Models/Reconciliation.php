<?php

namespace App\Models;

use Database\Factories\ReconciliationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reconciliation extends Model
{
    /** @use HasFactory<ReconciliationFactory> */
    use HasFactory;

    use SoftDeletes;

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_EXPENSE = 'expense';

    /**
     * Friendly aliases used in form requests / payloads.
     *
     * @var array<string, class-string>
     */
    public const MORPH_ALIAS_MAP = [
        self::TYPE_INVOICE => Invoice::class,
        self::TYPE_EXPENSE => Expense::class,
    ];

    protected $fillable = [
        'bank_transaction_id',
        'matchable_type',
        'matchable_id',
        'matched_amount',
        'matched_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'matched_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<BankTransaction, $this> */
    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    /** @return MorphTo<Model, $this> */
    public function matchable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by_user_id');
    }
}
