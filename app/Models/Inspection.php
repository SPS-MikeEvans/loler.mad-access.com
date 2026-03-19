<?php

namespace App\Models;

use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'kit_item_id',
        'inspector_user_id',
        'invoice_id',
        'status',
        'started_at',
        'inspection_date',
        'next_due_date',
        'overall_status',
        'report_notes',
        'pdf_path',
        'digital_sig_path',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'next_due_date'   => 'date',
            'started_at'      => 'datetime',
            'created_at'      => 'datetime',
            'cost'            => 'decimal:2',
        ];
    }

    /** @param Builder<Inspection> $query */
    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    /** @param Builder<Inspection> $query */
    public function scopeComplete(Builder $query): void
    {
        $query->where('status', 'complete');
    }

    /** @return BelongsTo<KitItem, $this> */
    public function kitItem(): BelongsTo
    {
        return $this->belongsTo(KitItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    /** @return HasMany<InspectionCheck, $this> */
    public function checks(): HasMany
    {
        return $this->hasMany(InspectionCheck::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
