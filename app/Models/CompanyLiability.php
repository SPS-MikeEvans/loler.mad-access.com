<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyLiability extends Model
{
    protected $fillable = [
        'terms_and_conditions',
        'insurances',
    ];

    protected function casts(): array
    {
        return [
            'insurances' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
