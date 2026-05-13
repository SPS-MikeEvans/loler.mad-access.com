<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSetting>
 */
class BusinessSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['Tide', 'Starling', 'Monzo Business', 'Barclays']),
            'account_holder' => fake()->company(),
            'sort_code' => str_pad((string) fake()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
            'account_number' => str_pad((string) fake()->numberBetween(0, 99999999), 8, '0', STR_PAD_LEFT),
            'iban' => 'GB29NWBK60161331926819',
            'reference_instructions' => 'Please use invoice number as the payment reference.',
            'payment_terms_days' => 14,
        ];
    }
}
