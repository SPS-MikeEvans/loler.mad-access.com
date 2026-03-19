<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LOLER Inspection Report — {{ $client->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .brand-header { width: 100%; background: #001F3F; border-collapse: collapse; }
        .brand-header td { vertical-align: middle; padding: 8px 14px; border: none; }
        .logo-mad    { font-size: 16px; font-weight: bold; color: #FFFFFF; }
        .logo-access { font-size: 16px; font-weight: bold; color: #FF4136; }
        .logo-sub    { font-size: 7px; color: #ffffffaa; margin-top: 2px; }
        .contact-cell { text-align: right; color: #ffffffbb; font-size: 8px; line-height: 1.6; }
        .brand-divider { height: 3px; background: #FFDC00; margin-bottom: 16px; }
        .page-inner { padding: 0 14px 14px; }
        .report-meta { margin-bottom: 14px; }
        .report-meta h1 { font-size: 14px; color: #001F3F; margin-bottom: 4px; }
        .report-meta .meta { color: #555; font-size: 9px; }
        .section-title { font-size: 11px; font-weight: bold; color: #001F3F; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #001F3F; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 6px 8px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-pass { background: #d1fae5; color: #065f46; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .badge-conditional { background: #fef3c7; color: #92400e; }
        .total-row td { font-weight: bold; background: #f0f4ff; border-top: 2px solid #001F3F; }
        .footer { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 8px; color: #888; text-align: center; }
        .no-data { padding: 20px; text-align: center; color: #888; font-style: italic; }
    </style>
</head>
<body>

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

    <div class="report-meta">
        <h1>LOLER Thorough Examination Report</h1>
        <div class="meta">
            <strong>Client:</strong> {{ $client->name }} &nbsp;&nbsp;
            <strong>Period:</strong> {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }} &nbsp;&nbsp;
            <strong>Generated:</strong> {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <p class="section-title">Inspection Summary</p>

    @if ($inspections->isEmpty())
        <p class="no-data">No inspections recorded for this client in the selected period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Equipment Type</th>
                    <th>Asset Tag</th>
                    <th>Serial No.</th>
                    <th>Inspector</th>
                    <th>Result</th>
                    <th style="text-align:right;">Cost (£)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inspections as $row)
                    @php
                        $insp = $row['inspection'];
                        $item = $row['item'];
                        $badgeClass = match($insp->overall_status) {
                            'pass' => 'badge-pass',
                            'fail' => 'badge-fail',
                            default => 'badge-conditional',
                        };
                        $label = match($insp->overall_status) {
                            'pass' => 'Pass',
                            'fail' => 'Fail',
                            default => 'Conditional',
                        };
                    @endphp
                    <tr>
                        <td>{{ $insp->inspection_date->format('d M Y') }}</td>
                        <td>{{ $item->kitType->name }}</td>
                        <td>{{ $item->asset_tag ?? '—' }}</td>
                        <td>{{ $item->serial_no ?? '—' }}</td>
                        <td>{{ $insp->inspector->name }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                        <td style="text-align:right;">{{ $insp->cost !== null ? number_format((float) $insp->cost, 2) : '—' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6">Total</td>
                    <td style="text-align:right;">£{{ number_format($total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated by MaD-ACCESS LOLER Inspection Management System &mdash; {{ now()->format('d M Y H:i') }}<br>
        Reports are generated from recorded inspection data and do not constitute a legal certificate of thorough examination.
    </div>

    </div>{{-- .page-inner --}}

</body>
</html>
