<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('sort_code', 8)->nullable();
            $table->string('account_number', 8)->nullable();
            $table->string('iban', 34)->nullable();
            $table->text('reference_instructions')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(14);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
