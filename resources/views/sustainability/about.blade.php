@extends('layouts.app')

@section('title', 'About EcoCycle Smart')

@section('content')
    <section class="page-hero compact">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">About EcoCycle Smart</span>
                <h1>Simple e-waste recycling for India.</h1>
                <p>EcoCycle Smart brings device intelligence, facility routing, pickup planning, rewards, and QR proof into one practical workflow.</p>
            </div>
            <a class="eco-button eco-button-primary" href="{{ route('sustainability.index') }}#deviceForm">
                <i data-lucide="scan-line"></i>
                <span>Try scan</span>
            </a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-4">
        <article class="metric-panel"><span>Centers</span><strong>{{ $coverageStats['centers'] }}</strong></article>
        <article class="metric-panel"><span>States</span><strong>{{ $coverageStats['states'] }}</strong></article>
        <article class="metric-panel"><span>Pickups</span><strong>{{ $coverageStats['pickup_enabled'] }}</strong></article>
        <article class="metric-panel"><span>Certificates</span><strong>{{ $coverageStats['certificate_enabled'] }}</strong></article>
    </section>

    <section class="mt-5 info-grid">
        <article class="info-card green"><i data-lucide="map-pinned"></i><h2>Route</h2><p>Find nearby centers with service filters, open status, and directions.</p></article>
        <article class="info-card blue"><i data-lucide="brain-circuit"></i><h2>Decide</h2><p>Compare repair, donation, recycling, and special handling recommendations.</p></article>
        <article class="info-card amber"><i data-lucide="badge-check"></i><h2>Prove</h2><p>Generate downloadable certificates with QR verification after disposal.</p></article>
    </section>

    <section class="section-band">
        <div class="section-head">
            <div>
                <span class="eyebrow">Why it works</span>
                <h2>Built for everyday users and organized drives</h2>
                <p>The interface stays simple for families while still supporting offices, campuses, apartments, and local collection teams.</p>
            </div>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Fast first action</strong><p>Scan or type a device and get guidance instantly.</p></div></div>
            <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Responsible channel</strong><p>Facilities are modeled around authorized recycler routing.</p></div></div>
            <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Motivation loop</strong><p>Points, challenges, and badges make repeated recycling easier.</p></div></div>
        </div>
    </section>
@endsection
