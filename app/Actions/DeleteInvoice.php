<?php

namespace App\Actions;

use App\Models\Invoice;

class DeleteInvoice
{
    /**
     * Unlink the invoice's inspections (resetting any waivers) and soft-delete the invoice.
     * Idempotent under SoftDeletes: calling this on an already-trashed invoice is a no-op.
     */
    public function cascade(Invoice $invoice): void
    {
        if ($invoice->trashed()) {
            return;
        }

        $invoice->inspections()->update([
            'invoice_id' => null,
            'invoice_waived' => false,
        ]);

        $invoice->delete();
    }
}
