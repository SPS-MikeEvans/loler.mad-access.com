<?php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_holder',
        'sort_code',
        'account_number',
        'iban',
        'reference_instructions',
        'payment_terms_days',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        /** @var self $row */
        $row = static::query()->firstOrCreate(['id' => 1], []);

        return $row;
    }

    public function hasBanking(): bool
    {
        return filled($this->bank_name)
            && filled($this->account_holder)
            && filled($this->sort_code)
            && filled($this->account_number);
    }
}
