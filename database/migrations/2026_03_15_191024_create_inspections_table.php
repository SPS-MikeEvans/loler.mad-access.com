<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_user_id')->constrained('users')->restrictOnDelete();
            $table->date('inspection_date');
            $table->date('next_due_date');
            $table->enum('overall_status', ['pass', 'fail', 'conditional']);
            $table->text('report_notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('digital_sig_path')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
