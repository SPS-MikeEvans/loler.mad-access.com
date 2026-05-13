<x-mail::message>
# Invoice {{ $invoice->invoice_number }} — {{ $daysOverdue }} days overdue

Hello {{ $invoice->client->contact_name ?? $invoice->client->name }},

Our records show invoice **{{ $invoice->invoice_number }}** is **{{ $daysOverdue }} days past its due date**
@if($invoice->due_date)
(due {{ $invoice->due_date->format('d F Y') }})
@endif
and remains unpaid.

- **Amount due:** £{{ number_format($invoice->total_amount, 2) }}
- **Reference:** {{ $invoice->invoice_number }}

Please arrange payment at your earliest convenience. Bank details are on the attached PDF.

If payment has already been made or there is a query holding it up, please reply so we can update our records.

Thanks,<br>
{{ $companyName }}
</x-mail::message>
