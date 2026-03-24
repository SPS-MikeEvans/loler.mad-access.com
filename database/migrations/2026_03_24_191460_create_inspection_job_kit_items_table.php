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
        Schema::create('inspection_job_kit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_job_id')->constrained('inspection_jobs')->cascadeOnDelete();
            $table->foreignId('kit_item_id')->constrained()->restrictOnDelete();
            $table->text('condition_notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_job_id', 'kit_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_job_kit_items');
    }
};
