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
        Schema::table('users', function (Blueprint $table) {
            $table->text('qualifications')->nullable()->after('competent_person_flag');
            $table->date('qualification_expiry')->nullable()->after('qualifications');
            $table->string('phone', 50)->nullable()->after('qualification_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qualifications', 'qualification_expiry', 'phone']);
        });
    }
};
