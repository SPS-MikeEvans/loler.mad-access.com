<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Flip sent invoices past their due date to overdue.';

    public function handle(): int
    {
        $count = Invoice::query()
            ->where('status', InvoiceStatus::Sent->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNull('paid_at')
            ->update([
                'status' => InvoiceStatus::Overdue->value,
                'updated_at' => now(),
            ]);

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
