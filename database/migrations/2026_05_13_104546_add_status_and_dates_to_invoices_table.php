<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 20)->default('sent')->after('total_amount');
            $table->date('due_date')->nullable()->after('issued_date');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('sent_at');
            $table->timestamp('last_chase_sent_at')->nullable()->after('paid_at');

            $table->index('status');
            $table->index('due_date');
            $table->index('paid_at');
            $table->index(['status', 'due_date'], 'invoices_status_due_date_idx');
        });

        DB::table('invoices')
            ->whereNull('sent_at')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('invoices')->where('id', $row->id)->update([
                    'status' => 'sent',
                    'sent_at' => $row->created_at,
                    'due_date' => Carbon::parse($row->issued_date)->addDays(14)->toDateString(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_due_date_idx');
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['status', 'due_date', 'sent_at', 'paid_at', 'last_chase_sent_at']);
        });
    }
};
