<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB cascade only fires on hard delete; soft-delete cascade is emulated in services.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->cascadeOnDelete();
            $table->string('matchable_type', 80);
            $table->unsignedBigInteger('matchable_id');
            $table->decimal('matched_amount', 12, 2);
            $table->foreignId('matched_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['matchable_type', 'matchable_id'], 'reconciliations_matchable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
