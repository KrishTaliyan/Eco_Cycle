@extends('layouts.app')

@section('title', 'Learn E-Waste Safety')

@section('content')
    <section class="page-hero compact">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Safety guide</span>
                <h1>Handle old electronics safely.</h1>
                <p>Use these checks before recycling phones, laptops, displays, appliances, chargers, and batteries.</p>
            </div>
            <a class="eco-button eco-button-primary" href="{{ route('sustainability.index') }}#deviceForm">
                <i data-lucide="scan-line"></i>
                <span>Check device</span>
            </a>
        </div>
    </section>

    <section class="info-grid">
        <article class="info-card danger"><i data-lucide="triangle-alert"></i><h2>Hazards</h2><p>Lead, mercury, cadmium, lithium, and flame retardants need controlled handling.</p></article>
        <article class="info-card blue"><i data-lucide="waves"></i><h2>Environment</h2><p>Informal dumping can pollute air, soil, drains, and groundwater.</p></article>
        <article class="info-card green"><i data-lucide="heart-pulse"></i><h2>Health</h2><p>Certified channels reduce exposure for families, workers, and communities.</p></article>
    </section>

    <section class="section-band">
        <div class="section-head">
            <div>
                <span class="eyebrow">Before disposal</span>
                <h2>Five practical steps</h2>
                <p>Small actions make collection safer and more useful.</p>
            </div>
        </div>
        <div class="steps-grid mt-4">
            <div><strong>1</strong><span>Back up and wipe personal data.</span></div>
            <div><strong>2</strong><span>Remove SIM cards, memory cards, and loose accessories.</span></div>
            <div><strong>3</strong><span>Repair or donate working devices before recycling.</span></div>
            <div><strong>4</strong><span>Keep swollen batteries away from heat and pressure.</span></div>
            <div><strong>5</strong><span>Use an authorized center and keep the certificate.</span></div>
        </div>
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-3">
        <article class="surface p-4 sm:p-5">
            <span class="eyebrow">Data devices</span>
            <h2 class="mt-1 text-xl font-black text-zinc-950">Phones and laptops</h2>
            <div class="mt-4 grid gap-2">
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Sign out</strong><p>Remove accounts and device locks before donation or resale.</p></div></div>
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Reset</strong><p>Factory reset phones and wipe storage drives where possible.</p></div></div>
            </div>
        </article>
        <article class="surface p-4 sm:p-5">
            <span class="eyebrow">Battery risk</span>
            <h2 class="mt-1 text-xl font-black text-zinc-950">Power banks and cells</h2>
            <div class="mt-4 grid gap-2">
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Tape terminals</strong><p>Cover exposed terminals and avoid loose metal contact.</p></div></div>
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Do not crush</strong><p>Damaged lithium cells can overheat or ignite.</p></div></div>
            </div>
        </article>
        <article class="surface p-4 sm:p-5">
            <span class="eyebrow">Large items</span>
            <h2 class="mt-1 text-xl font-black text-zinc-950">TVs and appliances</h2>
            <div class="mt-4 grid gap-2">
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Keep intact</strong><p>Do not break screens, compressors, or sealed parts at home.</p></div></div>
                <div class="check-row"><span><i data-lucide="check"></i></span><div><strong>Request pickup</strong><p>Use doorstep collection for heavy devices and bulk drives.</p></div></div>
            </div>
        </article>
    </section>
@endsection
