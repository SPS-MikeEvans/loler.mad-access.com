<?php

namespace Database\Factories;

use App\Models\KitItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KitItem>
 */
class KitItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_tag' => fake()->unique()->bothify('ASSET-####'),
            'manufacturer' => fake()->company(),
            'model' => fake()->word(),
            'serial_no' => fake()->bothify('SN-######'),
            'status' => 'in_service',
            'flagged_for_inspection' => false,
            'flag_notes' => null,
            'lifting_people' => false,
        ];
    }
}
