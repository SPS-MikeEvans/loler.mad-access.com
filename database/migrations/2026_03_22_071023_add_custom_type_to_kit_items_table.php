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
        Schema::table('kit_items', function (Blueprint $table) {
            $table->dropForeign(['kit_type_id']);
            $table->foreignId('kit_type_id')->nullable()->change()->constrained()->nullOnDelete();
            $table->string('custom_type_name', 100)->nullable()->after('kit_type_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('kit_items', function (Blueprint $table) {
            $table->dropColumn('custom_type_name');
            $table->foreignId('kit_type_id')->nullable(false)->change()->constrained()->restrictOnDelete();
        });
    }
};
