<?php

namespace App\Models;

use Database\Factories\KitTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SoftDeletes added 2026-05-09. KitTypeController blocks deletion when any kit_item references the type,
 * so trashed types never have live referrers. The `kit_types_name_brand_unique` index still applies to
 * trashed rows — recreating a trashed type with the same (name, brand) is rejected at the DB layer.
 */
class KitType extends Model
{
    /** @use HasFactory<KitTypeFactory> */
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'category',
        'brand',
        'interval_months',
        'lifts_people',
        'swl_description',
        'inspection_price',
        'checklist_json',
        'instructions',
        'resources_links',
        'spec_pdf_path',
        'inspection_pdf_path',
        'ai_suggested',
    ];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'lifts_people' => 'boolean',
            'inspection_price' => 'float',
            'checklist_json' => 'array',
            'resources_links' => 'array',
            'ai_suggested' => 'boolean',
        ];
    }

    /** @return HasMany<KitItem, $this> */
    public function kitItems(): HasMany
    {
        return $this->hasMany(KitItem::class);
    }
}
