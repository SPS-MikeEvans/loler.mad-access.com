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
        Schema::create('inspection_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('job_number')->unique();
            $table->enum('status', ['draft', 'open', 'in_progress', 'complete', 'returned'])->default('draft');
            $table->string('bag_qr_code')->unique();
            $table->text('notes')->nullable();
            $table->timestamp('drop_off_at')->nullable();
            $table->string('drop_off_signature_path')->nullable();
            $table->string('drop_off_signed_by')->nullable();
            $table->string('drop_off_ip', 45)->nullable();
            $table->timestamp('return_at')->nullable();
            $table->string('return_signature_path')->nullable();
            $table->string('return_signed_by')->nullable();
            $table->string('return_ip', 45)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_jobs');
    }
};
