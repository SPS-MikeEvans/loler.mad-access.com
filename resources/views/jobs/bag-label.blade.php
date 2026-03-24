<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bag Label — {{ $job->job_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: white; }

        .label {
            width: 105mm;
            min-height: 148mm;
            padding: 8mm;
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            gap: 4mm;
            page-break-inside: avoid;
        }

        .label-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 4mm;
        }

        .job-number {
            font-size: 22pt;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #111;
            line-height: 1;
        }

        .app-name {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
        }

        .client-name {
            font-size: 14pt;
            font-weight: 700;
            color: #222;
        }

        .meta {
            font-size: 8pt;
            color: #555;
            line-height: 1.6;
        }

        .items-section {
            border-top: 1px solid #ddd;
            padding-top: 3mm;
        }

        .items-heading {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 2mm;
            letter-spacing: 0.5px;
        }

        .item-line {
            font-size: 8pt;
            color: #333;
            line-height: 1.5;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: auto;
            padding-top: 3mm;
        }

        .qr-section svg { width: 55mm; height: 55mm; }

        .qr-caption {
            font-size: 7pt;
            color: #777;
            margin-top: 2mm;
            text-align: center;
        }

        @media print {
            @page { size: A6; margin: 0; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="label-header">
            <div>
                <p class="app-name">LOLER Inspection</p>
                <p class="job-number">{{ $job->job_number }}</p>
            </div>
        </div>

        <p class="client-name">{{ $job->client->name }}</p>

        <div class="meta">
            <div>Created: {{ $job->created_at->format('d M Y') }}</div>
            @if ($job->drop_off_at)
                <div>Dropped off: {{ $job->drop_off_at->format('d M Y H:i') }}</div>
            @endif
            <div>Items: {{ $job->kitItems->count() }}</div>
        </div>

        @if ($job->kitItems->isNotEmpty())
            <div class="items-section">
                <p class="items-heading">Contents</p>
                @foreach ($job->kitItems->take(8) as $item)
                    <p class="item-line">• {{ $item->typeName() }}{{ $item->asset_tag ? ' (' . $item->asset_tag . ')' : '' }}</p>
                @endforeach
                @if ($job->kitItems->count() > 8)
                    <p class="item-line" style="color:#999">+ {{ $job->kitItems->count() - 8 }} more items</p>
                @endif
            </div>
        @endif

        <div class="qr-section">
            {!! $qrSvg !!}
            <p class="qr-caption">Scan to open job details</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => window.print())
    </script>
</body>
</html>
