<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_number',
        'issued_date',
        'period_from',
        'period_to',
        'notes',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'issued_date'  => 'date',
            'period_from'  => 'date',
            'period_to'    => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('issued_date', $year)->count();

        return 'INV-' . $year . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }
}
