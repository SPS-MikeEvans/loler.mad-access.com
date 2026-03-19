<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_liabilities', function (Blueprint $table) {
            $table->id();
            $table->longText('terms_and_conditions')->nullable();
            $table->json('insurances')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_liabilities');
    }
};
