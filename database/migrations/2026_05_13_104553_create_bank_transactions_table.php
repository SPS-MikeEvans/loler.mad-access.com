<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cascade to reconciliations is emulated in the service layer because of SoftDeletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_connection_id')->constrained('bank_connections')->cascadeOnDelete();
            $table->string('external_id', 120)->index();
            $table->date('booking_date')->index();
            $table->date('value_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('counterparty_name')->nullable();
            $table->text('description')->nullable();
            $table->json('raw_payload');
            $table->timestamp('reconciled_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bank_connection_id', 'external_id'], 'bank_tx_connection_external_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
