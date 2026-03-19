<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE kit_items MODIFY COLUMN status ENUM('in_service','inspection_due','quarantined','retired') NOT NULL DEFAULT 'in_service'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE kit_items MODIFY COLUMN status ENUM('in_service','quarantined','retired') NOT NULL DEFAULT 'in_service'");
        }
    }
};
