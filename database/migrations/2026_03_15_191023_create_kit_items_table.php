<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kit_type_id')->constrained()->restrictOnDelete();
            $table->string('asset_tag')->unique();
            $table->string('qr_code')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_no')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('first_use_date')->nullable();
            $table->unsignedSmallInteger('swl_kg')->nullable();
            $table->boolean('lifting_people')->default(false);
            $table->enum('status', ['in_service', 'quarantined', 'retired'])->default('in_service');
            $table->date('next_inspection_due')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_items');
    }
};
