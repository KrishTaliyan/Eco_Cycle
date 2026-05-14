@extends('layouts.app')

@section('title', 'Learn E-Waste Safety')

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">Awareness</span>
        <h1>Know what is inside old electronics.</h1>
        <p>Short, useful safety guidance for customers.</p>
    </section>

    <section class="info-grid">
        <article class="info-card danger"><i data-lucide="triangle-alert"></i><h2>Hazards</h2><p>Lead, mercury, cadmium, lithium, arsenic, and plastic toxins need safe handling.</p></article>
        <article class="info-card blue"><i data-lucide="waves"></i><h2>Environment</h2><p>Improper dumping can pollute soil, water, air, and harm wildlife.</p></article>
        <article class="info-card green"><i data-lucide="heart-pulse"></i><h2>Health</h2><p>Unsafe recycling can affect the brain, kidneys, lungs, and nervous system.</p></article>
    </section>

    <section class="mt-5 surface p-4 sm:p-5">
        <div class="section-head">
            <div>
                <span class="eyebrow">Simple process</span>
                <h2>What customers should do</h2>
            </div>
        </div>
        <div class="steps-grid mt-4">
            <div><strong>1</strong><span>Back up and wipe data</span></div>
            <div><strong>2</strong><span>Remove SIM, cards, toner, and loose batteries</span></div>
            <div><strong>3</strong><span>Try repair or donation if usable</span></div>
            <div><strong>4</strong><span>Use an authorized collection channel</span></div>
            <div><strong>5</strong><span>Download QR certificate after disposal</span></div>
        </div>
    </section>
@endsection
