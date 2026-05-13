<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceChased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue,
    ) {}
}
