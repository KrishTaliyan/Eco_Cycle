@extends('layouts.app')

@section('title', 'About EcoCycle Smart')

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">About</span>
        <h1>A smarter way to handle e-waste in India.</h1>
        <p>EcoCycle Smart helps customers choose repair, donation, or certified recycling with less confusion.</p>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="metric-panel"><span>Mapped centers</span><strong>{{ $coverageStats['centers'] }}</strong></article>
        <article class="metric-panel"><span>States covered</span><strong>{{ $coverageStats['states'] }}</strong></article>
        <article class="metric-panel"><span>Pickup partners</span><strong>{{ $coverageStats['pickup_enabled'] }}</strong></article>
    </section>

    <section class="mt-5 info-grid">
        <article class="info-card green"><i data-lucide="map-pinned"></i><h2>India map</h2><p>City-wise center discovery with pickup, data wipe, battery, and certificate filters.</p></article>
        <article class="info-card blue"><i data-lucide="brain-circuit"></i><h2>Smart guidance</h2><p>Device analysis suggests repair, donation, special handling, or recycling.</p></article>
        <article class="info-card amber"><i data-lucide="badge-check"></i><h2>Proof</h2><p>Users can generate QR verified recycling certificates after disposal.</p></article>
    </section>
@endsection
