<?php

namespace App\Models;

use Database\Factories\KitItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitItem extends Model
{
    /** @use HasFactory<KitItemFactory> */
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'kit_group_id',
        'kit_type_id',
        'custom_type_name',
        'asset_tag',
        'qr_code',
        'manufacturer',
        'model',
        'serial_no',
        'purchase_date',
        'first_use_date',
        'swl_kg',
        'lifting_people',
        'status',
        'next_inspection_due',
        'flagged_for_inspection',
        'flag_notes',
        'pending_review',
    ];

    /** @var list<string> */
    public const LOCKED_FIELDS_AFTER_INSPECTION = [
        'manufacturer',
        'model',
        'serial_no',
        'first_use_date',
        'purchase_date',
        'kit_type_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'first_use_date' => 'date',
            'next_inspection_due' => 'date',
            'lifting_people' => 'boolean',
            'swl_kg' => 'integer',
            'flagged_for_inspection' => 'boolean',
            'pending_review' => 'boolean',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @param Builder<KitItem> $query */
    public function scopeFlaggedForInspection(Builder $query): void
    {
        $query->where('flagged_for_inspection', true);
    }

    /** @param Builder<KitItem> $query */
    public function scopeClientPending(Builder $query): void
    {
        $query->where('pending_review', true);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /** @return BelongsTo<KitGroup, $this> */
    public function kitGroup(): BelongsTo
    {
        return $this->belongsTo(KitGroup::class);
    }

    /** @return BelongsTo<KitType, $this> */
    public function kitType(): BelongsTo
    {
        return $this->belongsTo(KitType::class)->withTrashed();
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /** @return BelongsToMany<Job, $this> */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'inspection_job_kit_items', 'kit_item_id', 'inspection_job_id')
            ->withPivot('condition_notes')
            ->withTimestamps();
    }

    public function latestInspection(): ?Inspection
    {
        return $this->inspections()->latest('inspection_date')->first();
    }

    public function typeName(): string
    {
        return $this->kitType?->name ?? $this->custom_type_name ?? '—';
    }

    public function isCustomType(): bool
    {
        return $this->kit_type_id === null && $this->custom_type_name !== null;
    }

    public function hasInspections(): bool
    {
        return $this->inspections()->exists();
    }
}
