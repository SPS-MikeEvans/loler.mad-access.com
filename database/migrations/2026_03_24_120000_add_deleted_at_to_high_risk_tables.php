<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'deleted_at')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable();
            });
        }

        if (! Schema::hasColumn('kit_items', 'deleted_at')) {
            Schema::table('kit_items', function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable();
            });
        }

        if (! Schema::hasColumn('kit_types', 'deleted_at')) {
            Schema::table('kit_types', function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable();
            });
        }

        if (! Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'deleted_at')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('kit_items', 'deleted_at')) {
            Schema::table('kit_items', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('kit_types', 'deleted_at')) {
            Schema::table('kit_types', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }
    }
};
