<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $seedBrands = [
            'Petzl',
            'DMM Professional',
            'ISC',
            'Edelrid',
            'Teufelberger',
            'CAMP Safety',
            'Skylotec',
            'Singing Rock',
            'Kong',
            'Rock Exotica',
            'Courant',
            'Notch Equipment',
            'Marlow Ropes',
            'Yale Cordage',
        ];

        foreach ($seedBrands as $name) {
            DB::table('kit_brands')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_brands');
    }
};
