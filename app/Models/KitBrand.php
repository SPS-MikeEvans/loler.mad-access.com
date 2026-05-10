<?php

namespace App\Models;

use Database\Factories\KitBrandFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitBrand extends Model
{
    /** @use HasFactory<KitBrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<KitBrand> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
