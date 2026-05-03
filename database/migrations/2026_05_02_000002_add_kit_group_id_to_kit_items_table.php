<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kit_items', function (Blueprint $table) {
            $table->foreignId('kit_group_id')
                ->nullable()
                ->after('client_id')
                ->constrained('kit_groups')
                ->nullOnDelete();

            $table->index('kit_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('kit_items', function (Blueprint $table) {
            $table->dropForeign(['kit_group_id']);
            $table->dropIndex(['kit_group_id']);
            $table->dropColumn('kit_group_id');
        });
    }
};
