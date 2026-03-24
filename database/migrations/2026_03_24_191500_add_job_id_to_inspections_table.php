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
        Schema::table('inspections', function (Blueprint $table) {
            $table->foreignId('inspection_job_id')->nullable()->constrained('inspection_jobs')->nullOnDelete()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropForeign(['inspection_job_id']);
            $table->dropColumn('inspection_job_id');
        });
    }
};
