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
        Schema::table('kit_items', function (Blueprint $table): void {
            $table->boolean('flagged_for_inspection')->default(false)->after('status');
            $table->text('flag_notes')->nullable()->after('flagged_for_inspection');
        });
    }

    public function down(): void
    {
        Schema::table('kit_items', function (Blueprint $table): void {
            $table->dropColumn(['flagged_for_inspection', 'flag_notes']);
        });
    }
};
