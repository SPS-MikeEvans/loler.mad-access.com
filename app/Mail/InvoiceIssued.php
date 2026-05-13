<?php

namespace App\Mail;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {
        $this->invoice->loadMissing(['client', 'inspections.kitItem.kitType', 'inspections.inspector']);
    }

    public function envelope(): Envelope
    {
        $companyName = (string) config('company.name');

        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-issued',
            with: [
                'invoice' => $this->invoice,
                'companyName' => config('company.name'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.invoice', [
                    'invoice' => $this->invoice,
                    'company_name' => config('company.name'),
                    'company' => config('company'),
                    'businessSettings' => BusinessSetting::current(),
                ])->setPaper('a4', 'portrait')->output(),
                "invoice-{$this->invoice->invoice_number}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
