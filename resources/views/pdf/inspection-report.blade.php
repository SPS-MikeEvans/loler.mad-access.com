<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LOLER Thorough Examination Report</title>
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

        .doc-ref { text-align: right; font-size: 9pt; color: #333; margin-bottom: 5mm; }

        h1 { text-align: center; font-size: 15pt; color: #001F3F; margin: 5mm 0 8mm; border-top: 2px solid #001F3F; border-bottom: 2px solid #001F3F; padding: 3mm 0; }
        h2 { font-size: 11pt; color: #fff; background: #001F3F; border-left: 4px solid #FF4136; padding: 4px 8px; margin: 7mm 0 3mm; }

        /* Data tables */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
        table.data th { background: #f5f7fa; text-align: left; font-weight: bold; padding: 5px 8px; border: 1px solid #c8cdd6; width: 35%; font-size: 10pt; }
        table.data td { padding: 5px 8px; border: 1px solid #c8cdd6; font-size: 10pt; }

        /* Checklist table */
        table.checklist { width: 100%; border-collapse: collapse; margin-bottom: 4mm; font-size: 9.5pt; }
        table.checklist th { background: #001F3F; color: #fff; padding: 5px 7px; text-align: left; border: 1px solid #001F3F; }
        table.checklist td { padding: 5px 7px; border: 1px solid #c8cdd6; vertical-align: top; }
        table.checklist tr:nth-child(even) td { background: #f9fafc; }
        .status-pass { color: #065f46; font-weight: bold; }
        .status-fail { color: #991b1b; font-weight: bold; }
        .status-na  { color: #6b7280; }

        /* Photos */
        .photos-grid { display: flex; flex-wrap: wrap; gap: 4mm; margin-bottom: 5mm; }
        .photos-grid img { width: 55mm; height: 45mm; object-fit: cover; border: 1px solid #c8cdd6; border-radius: 2px; }
        .photo-caption { font-size: 8pt; color: #555; margin-top: 1mm; margin-bottom: 3mm; }

        /* Notes / defects box */
        .defects-box { background: #fffbeb; border: 1px solid #f59e0b; padding: 6px 10px; font-size: 10pt; margin-bottom: 5mm; }

        /* Verdict */
        .verdict { text-align: center; font-size: 14pt; font-weight: bold; padding: 8px; margin: 6mm 0; border: 3px solid; }
        .verdict-pass { color: #065f46; border-color: #065f46; background: #ecfdf5; }
        .verdict-fail { color: #991b1b; border-color: #991b1b; background: #fef2f2; }

        /* Signature */
        .sig-block { margin-top: 8mm; text-align: right; font-size: 10pt; }
        .sig-line { border-bottom: 1px solid #555; width: 80mm; display: inline-block; margin-left: 5mm; }
        .sig-img { max-height: 25mm; max-width: 70mm; margin-top: 3mm; border: 1px solid #ddd; }

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

    <div class="doc-ref">
        Report No: <strong>{{ $report_no }}</strong> &nbsp;&nbsp; Report Date: {{ $report_date }}
    </div>

    <h1>Certificate of Thorough Examination<br>
        <span style="font-size:10pt;font-weight:normal;">(LOLER 1998 — Schedule 1 Compliant)</span>
    </h1>

    {{-- ── Section 1: Equipment & Client ── --}}
    <h2>1. Equipment &amp; Client Details</h2>
    <table class="data">
        <tr><th>Client</th><td>{{ $inspection->kitItem->client->name }}</td></tr>
        <tr><th>Client Address</th><td>{{ $inspection->kitItem->client->address ?? 'Not recorded' }}</td></tr>
        <tr><th>Equipment Type</th><td>{{ $inspection->kitItem->kitType->name }}</td></tr>
        <tr><th>Asset Tag</th><td>{{ $inspection->kitItem->asset_tag ?? '—' }}</td></tr>
        <tr><th>Serial No.</th><td>{{ $inspection->kitItem->serial_no ?? '—' }}</td></tr>
        <tr><th>Manufacturer / Model</th><td>{{ ($inspection->kitItem->manufacturer ?? '—') }} / {{ ($inspection->kitItem->model ?? '—') }}</td></tr>
        <tr><th>Safe Working Load (SWL)</th><td>{{ $inspection->kitItem->swl_kg ? $inspection->kitItem->swl_kg . ' kg' : 'Not specified' }}</td></tr>
        <tr><th>Date of Examination</th><td>{{ $inspection->inspection_date->format('d F Y') }}</td></tr>
        <tr><th>Next Thorough Examination Due</th><td>{{ $inspection->next_due_date?->format('d F Y') ?? 'Not set' }}</td></tr>
    </table>

    {{-- ── Section 2: Inspector ── --}}
    <h2>2. Competent Person / Inspector</h2>
    <table class="data">
        <tr><th>Name</th><td>{{ $inspection->inspector->name }}</td></tr>
        <tr><th>Competent Person (LOLER)</th><td>{{ $inspection->inspector->competent_person_flag ? 'Yes' : 'No' }}</td></tr>
        @if ($inspection->inspector->qualifications)
            <tr><th>Qualifications / Experience</th><td>{{ $inspection->inspector->qualifications }}</td></tr>
        @endif
        @if ($inspection->inspector->qualification_expiry)
            <tr><th>Certificate Expiry</th><td>{{ $inspection->inspector->qualification_expiry->format('d F Y') }}</td></tr>
        @endif
    </table>

    {{-- ── Section 3: Checklist ── --}}
    <h2>3. Thorough Examination Results</h2>
    <table class="checklist">
        <thead>
            <tr>
                <th style="width:18%">Category</th>
                <th style="width:44%">Check / Item</th>
                <th style="width:10%">Result</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inspection->checks as $check)
                @php
                    $statusClass = match($check->status) {
                        'pass' => 'status-pass',
                        'fail' => 'status-fail',
                        default => 'status-na',
                    };
                    $statusLabel = match($check->status) {
                        'pass' => 'Pass',
                        'fail' => 'Fail',
                        'n/a'  => 'N/A',
                        default => ucfirst($check->status),
                    };
                @endphp
                <tr>
                    <td>{{ $check->check_category }}</td>
                    <td>{{ $check->check_text }}</td>
                    <td class="{{ $statusClass }}">{{ $statusLabel }}</td>
                    <td>{{ $check->notes ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Section 4: Photos ── --}}
    @php $checksWithPhotos = $inspection->checks->filter(fn($c) => $c->photos->isNotEmpty()); @endphp
    @if ($checksWithPhotos->isNotEmpty())
        <h2>4. Inspection Photos</h2>
        @foreach ($checksWithPhotos as $check)
            <p class="photo-caption"><strong>{{ $check->check_category }}</strong> — {{ $check->check_text }}</p>
            <div class="photos-grid">
                @foreach ($check->photos as $photo)
                    <img src="{{ storage_path('app/public/' . $photo->path) }}" alt="Inspection photo">
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- ── Section 5: Defects / Notes ── --}}
    @if ($inspection->report_notes)
        <h2>5. Defects, Repairs &amp; Additional Notes</h2>
        <div class="defects-box">{!! nl2br(e($inspection->report_notes)) !!}</div>
    @endif

    {{-- ── Verdict ── --}}
    <div class="verdict {{ $inspection->overall_status === 'pass' ? 'verdict-pass' : 'verdict-fail' }}">
        {{ $verdict }}
    </div>

    {{-- ── Signature ── --}}
    <div class="sig-block">
        <p>Signature of Competent Person:&nbsp;<span class="sig-line"></span></p>
        <p style="margin-top:4mm;">Date: {{ $inspection->inspection_date->format('d F Y') }}</p>
        @if ($inspection->digital_sig_path)
            <br>
            <img class="sig-img"
                 src="{{ storage_path('app/public/' . $inspection->digital_sig_path) }}"
                 alt="Inspector digital signature">
        @endif
    </div>

    {{-- ── Footer ── --}}
    <div class="doc-footer">
        This equipment must be re-examined before {{ $inspection->next_due_date?->format('d F Y') ?? 'the next due date' }}.<br>
        Records of thorough examination must be retained for a minimum of 2 years (LOLER 1998 Reg. 11).<br>
        This report is issued subject to our terms and conditions and current insurance arrangements.<br>
        Full liabilities and insurance certificates: {{ route('liabilities.public') }}<br>
        Generated by MaD-ACCESS LOLER Inspection Management System
    </div>

    </div>{{-- .page-inner --}}
</div>
</body>
</html>
