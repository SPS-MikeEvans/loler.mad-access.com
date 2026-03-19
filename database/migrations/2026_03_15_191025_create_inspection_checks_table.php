<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('check_category');
            $table->string('check_text');
            $table->enum('status', ['pass', 'fail', 'n/a']);
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_checks');
    }
};
