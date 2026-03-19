<?php

namespace App\Models;

use Database\Factories\InspectionCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionCheck extends Model
{
    /** @use HasFactory<InspectionCheckFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'inspection_id',
        'check_category',
        'check_text',
        'status',
        'notes',
    ];

    /** @return BelongsTo<Inspection, $this> */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /** @return HasMany<InspectionCheckPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(InspectionCheckPhoto::class);
    }
}
