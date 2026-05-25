@extends('layouts.app')

@section('title', 'EcoCycle Smart')

@section('page_data')
    <script>
        window.ecoInitial = {
            dashboard: @json($dashboard),
            cityPresets: @json($cityPresets),
            coverageStats: @json($coverageStats),
        };
    </script>
@endsection

@section('content')
    <section class="home-hero home-reveal">
        <div class="hero-copy">
            <span class="eyebrow">Smart e-waste</span>
            <h1>Scan. Pickup. Reward.</h1>
            <p>One clean flow for old phones, laptops, TVs, batteries, and appliances.</p>

            <div class="hero-actions">
                <a class="eco-button eco-button-primary" href="#deviceForm">
                    <i data-lucide="scan-line"></i>
                    <span>Scan now</span>
                </a>
                <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}">
                    <i data-lucide="map-pinned"></i>
                    <span>Centers</span>
                </a>
            </div>

            <div class="hero-stats-row" aria-label="EcoCycle quick stats">
                <div><strong>{{ $coverageStats['centers'] ?? '120+' }}</strong><span>Centers</span></div>
                <div><strong>{{ $coverageStats['pickup_enabled'] ?? '40+' }}</strong><span>Pickup ready</span></div>
                <div><strong>{{ $coverageStats['certificate_enabled'] ?? 'QR' }}</strong><span>Proof ready</span></div>
            </div>
        </div>

        <div class="hero-visual" aria-label="EcoCycle workflow illustration">
            <span class="scan-ring"></span>
            <span class="scan-beam"></span>
            <div class="device-orbit">
                <i data-lucide="scan-line"></i>
                <strong>Smart Scan</strong>
                <span>Instant device check</span>
            </div>
            <div class="device-orbit">
                <i data-lucide="map-pinned"></i>
                <strong>Nearby Centers</strong>
                <span>Distance and status</span>
            </div>
            <div class="device-orbit">
                <i data-lucide="gift"></i>
                <strong>Rewards</strong>
                <span>Points that grow</span>
            </div>
        </div>
    </section>

    <section class="home-command-grid home-reveal delay-1" aria-label="EcoCycle actions">
        <article class="surface scan-workbench">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Live scan</span>
                    <h2>Check a device</h2>
                </div>
                <span class="icon-badge"><i data-lucide="sparkles"></i></span>
            </div>

            <form id="deviceForm" class="mt-4 grid gap-4" enctype="multipart/form-data">
                <div class="flex flex-wrap gap-2" aria-label="Sample devices">
                    @foreach (['iPhone 11', 'Dell laptop', 'Samsung TV', 'Power bank'] as $model)
                        <button class="sample-chip" type="button" data-model="{{ $model }}">{{ $model }}</button>
                    @endforeach
                </div>

                <div class="grid gap-4 md:grid-cols-[1fr_0.7fr]">
                    <label class="field">
                        <span>Device</span>
                        <input name="model_name" type="text" placeholder="iPhone, laptop, TV..." required>
                    </label>
                    <label class="field">
                        <span>Condition</span>
                        <select name="condition">
                            <option value="unknown">Unknown</option>
                            <option value="working">Working</option>
                            <option value="minor repair">Minor repair</option>
                            <option value="not working">Not working</option>
                            <option value="battery risk">Battery risk</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <label class="file-drop compact-file">
                        <i data-lucide="image-plus"></i>
                        <span id="deviceImageLabel">Add photo</span>
                        <input id="deviceImageInput" name="device_image" type="file" accept="image/*">
                    </label>
                    <button class="eco-button eco-button-primary" type="submit">
                        <i data-lucide="scan-line"></i>
                        <span>Analyze</span>
                    </button>
                </div>
            </form>
        </article>

        <a class="quick-card command-card tall" href="{{ route('facilities') }}">
            <i data-lucide="map-pinned"></i>
            <strong>Find centers</strong>
            <span>{{ $coverageStats['centers'] }} trusted spots</span>
        </a>
        <a class="quick-card command-card wide" href="{{ route('pickup') }}">
            <i data-lucide="truck"></i>
            <strong>Book pickup</strong>
            <span>Doorstep or drive</span>
        </a>
        <a class="quick-card command-card" href="{{ route('rewards') }}">
            <i data-lucide="trophy"></i>
            <strong>Rewards</strong>
            <span>Points and badges</span>
        </a>
    </section>

    <section class="section-band home-reveal delay-2">
        <div class="section-head">
            <div>
                <span class="eyebrow">Network</span>
                <h2>Collection preview</h2>
            </div>
            <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}"><i data-lucide="arrow-right"></i><span>All centers</span></a>
        </div>
        <div class="facility-strip mt-4">
            @foreach (array_slice($facilityPreview, 0, 4) as $facility)
                <article class="facility-card">
                    <div>
                        <h3>{{ $facility['name'] }}</h3>
                        <p>{{ $facility['city'] }} - {{ $facility['state'] }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach (array_slice($facility['services'], 0, 2) as $service)
                                <span class="facility-tag">{{ $service }}</span>
                            @endforeach
                        </div>
                    </div>
                    <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}"><span>View</span></a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="feature-grid feature-grid-mosaic home-reveal delay-3" aria-label="Features">
        <a class="quick-card" href="{{ route('learn') }}"><i data-lucide="shield-check"></i><strong>Safety guide</strong><span>Data, battery, proof.</span></a>
        <a class="quick-card" href="{{ route('about') }}"><i data-lucide="brain-circuit"></i><strong>Smart routing</strong><span>Repair before recycle.</span></a>
        <a class="quick-card" href="{{ route('contact') }}"><i data-lucide="building-2"></i><strong>Bulk drive</strong><span>Office or society.</span></a>
        <a class="quick-card" href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i><strong>Workspace</strong><span>Track every action.</span></a>
    </section>

    @include('sustainability.partials.analysis-modal')
@endsection
