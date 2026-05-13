<?php

namespace App\Models;

use Database\Factories\BankConnectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankConnection extends Model
{
    /** @use HasFactory<BankConnectionFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_LINKED = 'linked';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'provider',
        'institution_id',
        'institution_name',
        'requisition_id',
        'requisition_reference',
        'agreement_id',
        'account_ids',
        'status',
        'linked_at',
        'expires_at',
        'last_synced_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'requisition_id' => 'encrypted',
            'agreement_id' => 'encrypted',
            'account_ids' => 'array',
            'linked_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /** @return HasMany<BankTransaction, $this> */
    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @param Builder<BankConnection> $query */
    public function scopeLinked(Builder $query): void
    {
        $query->where('status', self::STATUS_LINKED);
    }

    /** @param Builder<BankConnection> $query */
    public function scopeExpired(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function (Builder $inner) {
                    $inner->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());
                });
        });
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function needsRelink(): bool
    {
        return $this->isExpired() || $this->status === self::STATUS_REVOKED;
    }
}
