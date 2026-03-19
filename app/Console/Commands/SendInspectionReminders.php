<?php

namespace App\Console\Commands;

use App\Mail\InspectionDueSoon;
use App\Mail\InspectionOverdue;
use App\Models\KitItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInspectionReminders extends Command
{
    protected $signature = 'inspections:send-reminders';

    protected $description = 'Send 30-day due-soon warnings to clients and flag overdue items';

    public function handle(): void
    {
        $today = today();
        $warnFrom = $today->clone()->addDays(1);
        $warnTo = $today->clone()->addDays(30);

        $dueSoonCount = 0;
        $overdueCount = 0;

        KitItem::whereBetween('next_inspection_due', [$warnFrom, $warnTo])
            ->whereIn('status', ['in_service', 'inspection_due'])
            ->with('client', 'kitType')
            ->each(function (KitItem $item) use (&$dueSoonCount): void {
                Mail::to($item->client->contact_email)->send(new InspectionDueSoon($item));
                $dueSoonCount++;
            });

        KitItem::where('next_inspection_due', '<', $today)
            ->where('status', 'in_service')
            ->with('client', 'kitType')
            ->each(function (KitItem $item) use (&$overdueCount): void {
                $item->update(['status' => 'inspection_due']);
                Mail::to(config('mail.admin_address', config('mail.from.address')))->send(new InspectionOverdue($item));
                $overdueCount++;
            });

        $this->info("Reminders sent: {$dueSoonCount} due-soon, {$overdueCount} overdue.");
    }
}
