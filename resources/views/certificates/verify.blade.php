<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify {{ $certificate->certificate_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6fbf8] text-zinc-950 antialiased">
    <main class="mx-auto grid min-h-screen max-w-4xl place-items-center px-4 py-8">
        <article class="surface w-full p-5 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="section-kicker">Verified certificate</p>
                    <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">{{ $certificate->certificate_number }}</h1>
                    <p class="mt-3 text-sm leading-6 text-zinc-600">
                        Issued to {{ $certificate->holder_name }} on {{ $certificate->issued_at->format('F j, Y') }} for an India e-waste disposal workflow.
                    </p>
                </div>
                <img class="h-36 w-36 border border-zinc-200 bg-white p-2" src="{{ $qrCode }}" alt="QR verification code">
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="metric-tile">
                    <span class="metric-label">Device</span>
                    <strong>{{ $activity->device_model }}</strong>
                </div>
                <div class="metric-tile">
                    <span class="metric-label">CO2 reduced</span>
                    <strong>{{ $activity->co2_kg }}kg</strong>
                </div>
                <div class="metric-tile">
                    <span class="metric-label">Eco points</span>
                    <strong>{{ $activity->points_awarded }}</strong>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a class="eco-button eco-button-primary justify-center" href="{{ route('certificates.download', $certificate) }}">
                    <i data-lucide="download"></i>
                    <span>Download PDF</span>
                </a>
                <a class="eco-button eco-button-secondary justify-center" href="{{ route('sustainability.index') }}">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Open Dashboard</span>
                </a>
            </div>
        </article>
    </main>
</body>
</html>
