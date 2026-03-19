<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('category');
            $table->string('swl_description')->nullable()->after('lifts_people');
            $table->string('price_usd')->nullable()->after('swl_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->dropColumn(['brand', 'swl_description', 'price_usd']);
        });
    }
};
