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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('notes');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('subtotal');
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->boolean('invoice_waived')->default(false)->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn('invoice_waived');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_percent']);
        });
    }
};
