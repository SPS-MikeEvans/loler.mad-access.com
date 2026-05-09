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
        foreach (['clients', 'invoices', 'kit_types', 'kit_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('deleted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['clients', 'invoices', 'kit_types', 'kit_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex([$tableName.'_deleted_at_index']);
            });
        }
    }
};
