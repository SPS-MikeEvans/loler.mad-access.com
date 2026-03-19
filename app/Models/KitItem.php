<?php

namespace App\Models;

use Database\Factories\KitItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitItem extends Model
{
    /** @use HasFactory<KitItemFactory> */
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'kit_type_id',
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
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'first_use_date' => 'date',
            'next_inspection_due' => 'date',
            'lifting_people' => 'boolean',
            'swl_kg' => 'integer',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<KitType, $this> */
    public function kitType(): BelongsTo
    {
        return $this->belongsTo(KitType::class);
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function latestInspection(): ?Inspection
    {
        return $this->inspections()->latest('inspection_date')->first();
    }
}
