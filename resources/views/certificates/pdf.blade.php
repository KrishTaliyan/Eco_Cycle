<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #16312a; margin: 0; background: #f5faf7; }
        .page { padding: 34px; }
        .certificate { border: 2px solid #1f9d72; background: #ffffff; padding: 32px; }
        .top { display: table; width: 100%; border-bottom: 1px solid #cbd5d1; padding-bottom: 18px; }
        .top div { display: table-cell; vertical-align: middle; }
        .brand { font-size: 24px; font-weight: 700; color: #087f5b; }
        .number { text-align: right; font-size: 12px; color: #52635e; }
        h1 { font-size: 30px; margin: 30px 0 8px; color: #10231f; }
        p { line-height: 1.55; }
        .name { font-size: 22px; font-weight: 700; color: #0f766e; }
        .grid { display: table; width: 100%; margin-top: 24px; }
        .cell { display: table-cell; width: 33%; border: 1px solid #d8e6df; padding: 14px; }
        .cell span { display: block; color: #66746f; font-size: 11px; text-transform: uppercase; }
        .cell strong { display: block; margin-top: 6px; font-size: 18px; }
        .footer { display: table; width: 100%; margin-top: 30px; }
        .qr { display: table-cell; width: 180px; }
        .verify { display: table-cell; vertical-align: middle; font-size: 12px; color: #52635e; }
        .materials { margin-top: 22px; font-size: 12px; color: #52635e; }
    </style>
</head>
<body>
    <div class="page">
        <div class="certificate">
            <div class="top">
                <div class="brand">EcoCycle Smart</div>
                <div class="number">
                    Certificate {{ $certificate->certificate_number }}<br>
                    Issued {{ $certificate->issued_at->format('F j, Y') }}
                </div>
            </div>

            <h1>Recycling Certificate</h1>
            <p>This certifies that <span class="name">{{ $certificate->holder_name }}</span> recorded responsible disposal for <strong>{{ $activity->device_model }}</strong>.</p>
            <p>The device was categorized as <strong>{{ $activity->device_category }}</strong> and processed through an India-focused certified recycling workflow.</p>

            <div class="grid">
                <div class="cell">
                    <span>E-waste recycled</span>
                    <strong>{{ $activity->ewaste_kg }} kg</strong>
                </div>
                <div class="cell">
                    <span>CO2 reduced</span>
                    <strong>{{ $activity->co2_kg }} kg</strong>
                </div>
                <div class="cell">
                    <span>Eco points</span>
                    <strong>{{ $activity->points_awarded }}</strong>
                </div>
            </div>

            <div class="materials">
                Materials recovered:
                @foreach (($activity->materials_recovered ?? []) as $material)
                    {{ $material['name'] }} {{ $material['amount'] }}{{ $material['unit'] }}@if (! $loop->last), @endif
                @endforeach
            </div>

            <div class="footer">
                <div class="qr">
                    <img src="{{ $qrCode }}" width="150" height="150" alt="QR verification code">
                </div>
                <div class="verify">
                    Scan the QR code to verify this certificate.<br>
                    Verification URL: {{ $certificate->qr_payload }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
