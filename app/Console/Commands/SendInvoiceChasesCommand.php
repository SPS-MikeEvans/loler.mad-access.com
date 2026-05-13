<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceChased;
use App\Mail\InvoiceChaseReminder;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendInvoiceChasesCommand extends Command
{
    protected $signature = 'invoices:send-chases';

    protected $description = 'Queue chase reminders for overdue invoices at +7/+14/+30 day buckets, respecting a 7-day cooldown.';

    public function handle(): int
    {
        $today = Carbon::today();
        $cooldownThreshold = now()->subDays(7);
        $sent = 0;
        $skipped = 0;

        Invoice::query()
            ->where('status', InvoiceStatus::Overdue->value)
            ->whereNotNull('due_date')
            ->whereNull('paid_at')
            ->with('client')
            ->chunkById(50, function ($invoices) use ($today, $cooldownThreshold, &$sent, &$skipped): void {
                foreach ($invoices as $invoice) {
                    if ($invoice->last_chase_sent_at && $invoice->last_chase_sent_at->gt($cooldownThreshold)) {
                        $skipped++;

                        continue;
                    }

                    $daysOverdue = (int) $invoice->due_date->startOfDay()->diffInDays($today, absolute: true);
                    if ($invoice->due_date->isFuture()) {
                        $skipped++;

                        continue;
                    }

                    // Only chase at the meaningful buckets — first chase as soon as we cross 7d overdue,
                    // then again at the standard 7-day cooldown.
                    if ($daysOverdue < 7) {
                        $skipped++;

                        continue;
                    }

                    $recipient = $invoice->client->contact_email ?? null;
                    if (! $recipient) {
                        $skipped++;

                        continue;
                    }

                    Mail::to($recipient)->queue(new InvoiceChaseReminder($invoice, $daysOverdue));

                    $invoice->last_chase_sent_at = now();
                    $invoice->save();

                    InvoiceChased::dispatch($invoice, $daysOverdue);
                    $sent++;
                }
            });

        $this->info("Queued {$sent} chase email(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
