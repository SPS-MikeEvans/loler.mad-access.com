<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidInvoiceTransition;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SoftDeletes added 2026-05-09. Cascading from Client is centralized in Client's `deleting` listener.
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'invoice_number',
        'issued_date',
        'due_date',
        'period_from',
        'period_to',
        'notes',
        'subtotal',
        'discount_percent',
        'total_amount',
        'status',
        'sent_at',
        'paid_at',
        'last_chase_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'due_date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_chase_sent_at' => 'datetime',
        ];
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::withTrashed()->whereYear('issued_date', $year)->count();

        return 'INV-'.$year.'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /** @return MorphMany<Reconciliation, $this> */
    public function reconciliations(): MorphMany
    {
        return $this->morphMany(Reconciliation::class, 'matchable');
    }

    public function paidAmount(): float
    {
        return round((float) $this->reconciliations()->sum('matched_amount'), 2);
    }

    public function outstandingAmount(): float
    {
        return round((float) $this->total_amount - $this->paidAmount(), 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->outstandingAmount() <= 0.005;
    }

    public function isPartiallyPaid(): bool
    {
        $paid = $this->paidAmount();

        return $paid > 0 && ! $this->isFullyPaid();
    }

    public function daysOverdue(): int
    {
        if ($this->due_date === null) {
            return 0;
        }

        $diff = (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), absolute: false);

        return max(0, -$diff);
    }

    public function transitionTo(InvoiceStatus $next): void
    {
        $current = $this->status instanceof InvoiceStatus
            ? $this->status
            : InvoiceStatus::from((string) $this->status);

        if (! $current->canTransitionTo($next)) {
            throw new InvalidInvoiceTransition($current, $next);
        }

        $this->status = $next;
    }

    /** @param Builder<Invoice> $query */
    public function scopeUnpaid(Builder $query): void
    {
        $query->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value]);
    }

    /** @param Builder<Invoice> $query */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Overdue->value);
    }

    /** @param Builder<Invoice> $query */
    public function scopeChaseable(Builder $query): void
    {
        $query->where('status', InvoiceStatus::Overdue->value)
            ->whereNotNull('due_date')
            ->whereNull('paid_at');
    }
}
