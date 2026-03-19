<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->boolean('lifts_people')->default(true)->after('interval_months');
        });
    }

    public function down(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->dropColumn(['category', 'lifts_people']);
        });
    }
};
