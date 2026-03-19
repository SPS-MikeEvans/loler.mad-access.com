<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.5; color: #1a1a1a; }
        .page { padding: 0 0 15mm 0; }

        /* Branded header */
        .brand-header { width: 100%; background: #001F3F; border-collapse: collapse; }
        .brand-header td { vertical-align: middle; padding: 6mm 8mm; border: none; }
        .logo-mad    { font-size: 20pt; font-weight: bold; color: #FFFFFF; }
        .logo-access { font-size: 20pt; font-weight: bold; color: #FF4136; }
        .logo-sub    { font-size: 8pt; color: #ffffffaa; margin-top: 2px; }
        .contact-cell { text-align: right; color: #ffffffbb; font-size: 8.5pt; line-height: 1.6; }
        .brand-divider { height: 3px; background: #FFDC00; margin-bottom: 8mm; }
        .page-inner { padding: 0 18mm; }

        /* Invoice title block */
        .inv-title-block { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
        .inv-title-block td { vertical-align: top; padding: 0; border: none; }
        .inv-title { font-size: 22pt; font-weight: bold; color: #001F3F; letter-spacing: 0.05em; }
        .inv-meta { font-size: 9.5pt; color: #444; margin-top: 3mm; line-height: 1.7; }
        .inv-meta strong { color: #1a1a1a; }

        /* Bill to / Bill from */
        .parties { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
        .parties td { vertical-align: top; padding: 4px 8px; border: none; width: 50%; }
        .party-label { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #001F3F; margin-bottom: 3px; border-bottom: 2px solid #FF4136; padding-bottom: 2px; }
        .party-name { font-size: 11pt; font-weight: bold; color: #1a1a1a; margin-top: 3px; }
        .party-detail { font-size: 9pt; color: #555; line-height: 1.6; }

        /* Line items table */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 5mm; font-size: 9.5pt; }
        table.items th { background: #001F3F; color: #fff; padding: 5px 8px; text-align: left; border: 1px solid #001F3F; }
        table.items th.amount { text-align: right; }
        table.items td { padding: 5px 8px; border: 1px solid #d1d5db; vertical-align: top; }
        table.items tr:nth-child(even) td { background: #f9fafc; }
        table.items .amount-col { text-align: right; }
        table.items .total-row td { font-weight: bold; background: #f0f4ff; border-top: 2px solid #001F3F; }
        table.items .total-row .amount-col { font-size: 12pt; color: #001F3F; }

        /* Notes */
        .notes-box { background: #fffbeb; border: 1px solid #f59e0b; padding: 6px 10px; font-size: 9.5pt; margin-bottom: 6mm; }
        .notes-label { font-weight: bold; margin-bottom: 3px; }

        /* Footer */
        .doc-footer { margin-top: 10mm; padding-top: 4mm; border-top: 1px solid #ccc; font-size: 8.5pt; color: #666; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Branded Header ── --}}
    <table class="brand-header">
        <tr>
            <td>
                <span class="logo-mad">MaD-</span><span class="logo-access">ACCESS</span>
                <div class="logo-sub">LOLER Inspection Management</div>
            </td>
            <td class="contact-cell">
                @if(config('company.address')){{ config('company.address') }}<br>@endif
                @if(config('company.phone')){{ config('company.phone') }}<br>@endif
                @if(config('company.email')){{ config('company.email') }}@endif
            </td>
        </tr>
    </table>
    <div class="brand-divider"></div>

    <div class="page-inner">

    {{-- ── Invoice title ── --}}
    <table class="inv-title-block">
        <tr>
            <td>
                <div class="inv-title">INVOICE</div>
            </td>
            <td style="text-align:right;">
                <div class="inv-meta">
                    <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Issued:</strong> {{ $invoice->issued_date->format('d F Y') }}<br>
                    <strong>Period:</strong> {{ $invoice->period_from->format('d M Y') }} – {{ $invoice->period_to->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Parties ── --}}
    <table class="parties">
        <tr>
            <td>
                <div class="party-label">From</div>
                <div class="party-name">{{ $company_name }}</div>
                @if(config('company.address'))
                    <div class="party-detail">{{ config('company.address') }}</div>
                @endif
                @if(config('company.email'))
                    <div class="party-detail">{{ config('company.email') }}</div>
                @endif
            </td>
            <td>
                <div class="party-label">Bill To</div>
                <div class="party-name">{{ $invoice->client->name }}</div>
                @if($invoice->client->address)
                    <div class="party-detail">{{ $invoice->client->address }}</div>
                @endif
                @if($invoice->client->contact_email)
                    <div class="party-detail">{{ $invoice->client->contact_email }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Line Items ── --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:12%">Date</th>
                <th style="width:26%">Equipment</th>
                <th style="width:14%">Asset Tag</th>
                <th style="width:18%">Inspector</th>
                <th style="width:10%">Result</th>
                <th class="amount" style="width:20%">Amount (£)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->inspections->sortBy('inspection_date') as $inspection)
                <tr>
                    <td>{{ $inspection->inspection_date->format('d M Y') }}</td>
                    <td>{{ $inspection->kitItem->kitType->name }}</td>
                    <td>{{ $inspection->kitItem->asset_tag ?? $inspection->kitItem->serial_no ?? '—' }}</td>
                    <td>{{ $inspection->inspector->name ?? '—' }}</td>
                    <td>{{ ucfirst($inspection->overall_status) }}</td>
                    <td class="amount-col">{{ $inspection->cost !== null ? number_format((float) $inspection->cost, 2) : '—' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">Total</td>
                <td class="amount-col">£{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ── Notes ── --}}
    @if ($invoice->notes)
        <div class="notes-box">
            <div class="notes-label">Notes</div>
            {!! nl2br(e($invoice->notes)) !!}
        </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="doc-footer">
        This invoice is issued subject to our terms and conditions and current insurance arrangements.<br>
        Full liabilities and insurance certificates: {{ route('liabilities.public') }}<br>
        Generated by MaD-ACCESS LOLER Inspection Management System<br>
        Please retain this invoice for accounting and compliance purposes.
    </div>

    </div>{{-- .page-inner --}}
</div>
</body>
</html>
