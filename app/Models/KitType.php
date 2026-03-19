<?php

namespace App\Models;

use Database\Factories\KitTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitType extends Model
{
    /** @use HasFactory<KitTypeFactory> */
    use HasFactory;

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
    ];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'lifts_people' => 'boolean',
            'checklist_json' => 'array',
            'resources_links' => 'array',
        ];
    }

    /** @return HasMany<KitItem, $this> */
    public function kitItems(): HasMany
    {
        return $this->hasMany(KitItem::class);
    }
}
