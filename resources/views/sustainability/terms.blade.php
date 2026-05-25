@extends('layouts.app')

@section('title', 'Terms')

@section('content')
    <section class="page-hero compact home-reveal">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Terms</span>
                <h1>Clear rules. Clean recycling.</h1>
                <p>Use EcoCycle for guidance, pickup planning, rewards, and proof records.</p>
            </div>
            <a class="eco-button eco-button-primary" href="{{ route('sustainability.index') }}#deviceForm">
                <i data-lucide="scan-line"></i>
                <span>Start</span>
            </a>
        </div>
    </section>

    <section class="info-grid">
        <article class="info-card green"><i data-lucide="shield-check"></i><h2>Use</h2><p>Enter accurate device and pickup details.</p></article>
        <article class="info-card blue"><i data-lucide="file-badge"></i><h2>Proof</h2><p>Certificates reflect submitted disposal records.</p></article>
        <article class="info-card amber"><i data-lucide="lock"></i><h2>Data</h2><p>Wipe personal data before donation or recycling.</p></article>
    </section>
@endsection
