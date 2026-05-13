<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cascade to bank_transactions and reconciliations is emulated in the service layer
 * because all three tables use SoftDeletes (DB cascade only fires on hard delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->default('gocardless');
            $table->string('institution_id', 80);
            $table->string('institution_name')->nullable();
            $table->text('requisition_id')->nullable();          // encrypted
            $table->string('requisition_reference', 64)->unique();
            $table->text('agreement_id')->nullable();            // encrypted
            $table->json('account_ids')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
