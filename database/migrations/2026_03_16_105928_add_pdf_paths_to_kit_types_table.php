<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->string('spec_pdf_path')->nullable()->after('resources_links');
            $table->string('inspection_pdf_path')->nullable()->after('spec_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('kit_types', function (Blueprint $table) {
            $table->dropColumn(['spec_pdf_path', 'inspection_pdf_path']);
        });
    }
};
