<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kit_types', function (Blueprint $table): void {
            $table->boolean('ai_suggested')->default(false)->after('inspection_price');
            $table->unique(['name', 'brand'], 'kit_types_name_brand_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kit_types', function (Blueprint $table): void {
            $table->dropUnique('kit_types_name_brand_unique');
            $table->dropColumn('ai_suggested');
        });
    }
};
