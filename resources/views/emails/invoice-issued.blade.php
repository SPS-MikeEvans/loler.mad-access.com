<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Hello {{ $invoice->client->contact_name ?? $invoice->client->name }},

Please find attached your invoice **{{ $invoice->invoice_number }}** for the period
**{{ $invoice->period_from->format('d M Y') }} – {{ $invoice->period_to->format('d M Y') }}**.

- **Total due:** £{{ number_format($invoice->total_amount, 2) }}
@if($invoice->due_date)
- **Payment due by:** {{ $invoice->due_date->format('d F Y') }}
@endif

Payment instructions are included on the attached PDF. Please use the invoice number as your payment reference.

If you have any questions, just reply to this email.

Thanks,<br>
{{ $companyName }}
</x-mail::message>
