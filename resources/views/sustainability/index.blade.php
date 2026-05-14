@extends('layouts.app')

@section('title', 'EcoCycle Smart India')

@section('page_data')
    <script>
        window.ecoInitial = {
            dashboard: @json($dashboard),
            deviceCatalog: @json($deviceCatalog),
            cityPresets: @json($cityPresets),
            coverageStats: @json($coverageStats),
        };
    </script>
@endsection

@section('content')
    <section class="home-hero">
        <div class="hero-copy">
            <span class="eyebrow">India e-waste recycling</span>
            <h1>Recycle electronics safely. Earn rewards.</h1>
            <p>Scan a device, find a nearby center, plan pickup, and download a verified certificate.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a class="eco-button eco-button-primary" href="#deviceForm"><i data-lucide="scan-line"></i><span>Scan device</span></a>
                <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}"><i data-lucide="map-pin"></i><span>Find center</span></a>
            </div>
            <div class="hero-stats">
                <div><strong>{{ $coverageStats['centers'] }}</strong><span>Centers</span></div>
                <div><strong>{{ $coverageStats['states'] }}</strong><span>States</span></div>
                <div><strong data-dashboard="user.points">0</strong><span>Your points</span></div>
            </div>
        </div>

        <form id="deviceForm" class="action-card" enctype="multipart/form-data">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <span class="eyebrow">AI scan</span>
                    <h2>Check your device</h2>
                </div>
                <span class="icon-badge green"><i data-lucide="sparkles"></i></span>
            </div>
            <label class="field mt-4">
                <span>Device model</span>
                <input name="model_name" list="deviceExamples" type="text" placeholder="Redmi Note 10, laptop, TV">
            </label>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
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
                <label class="field">
                    <span>Image</span>
                    <input name="device_image" type="file" accept="image/*">
                </label>
            </div>
            <datalist id="deviceExamples">
                @foreach ($deviceCatalog as $device)
                    @foreach ($device['examples'] as $example)
                        <option value="{{ $example }}"></option>
                    @endforeach
                @endforeach
            </datalist>
            <div class="mt-3 flex flex-wrap gap-2">
                <button class="sample-chip" type="button" data-model="Redmi Note 10">Phone</button>
                <button class="sample-chip" type="button" data-model="Dell Inspiron Laptop">Laptop</button>
                <button class="sample-chip" type="button" data-model="Samsung LED TV">TV</button>
                <button class="sample-chip" type="button" data-model="Old power bank">Battery</button>
            </div>
            <button class="eco-button eco-button-primary mt-4 w-full justify-center" type="submit">
                <i data-lucide="zap"></i>
                <span>Analyze Device</span>
            </button>
        </form>
    </section>

    <section class="quick-actions">
        <a class="quick-card" href="{{ route('facilities') }}"><i data-lucide="map"></i><strong>Find centers</strong><span>India map and service filters</span></a>
        <a class="quick-card" href="{{ route('pickup') }}"><i data-lucide="truck"></i><strong>Plan pickup</strong><span>Home, office, society, campus</span></a>
        <a class="quick-card" href="{{ route('learn') }}"><i data-lucide="shield-alert"></i><strong>Learn safety</strong><span>Hazards and disposal rules</span></a>
        <a class="quick-card" href="{{ route('rewards') }}"><i data-lucide="trophy"></i><strong>Rewards</strong><span>Points, badges, certificates</span></a>
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-[1fr_0.9fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Nearest center</span>
                    <h2>Pick your city</h2>
                </div>
                <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}">Full map</a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                <label class="field">
                    <span>City</span>
                    <select id="citySelect">
                        @foreach ($cityPresets as $city)
                            <option value="{{ $city['city'] }}">{{ $city['city'] }} - {{ $city['state'] }}</option>
                        @endforeach
                    </select>
                </label>
                <button id="useCityBtn" class="eco-button eco-button-primary self-end justify-center" type="button">Show</button>
            </div>
            <p id="locationStatus" class="mt-3 text-sm text-zinc-500">Choose a city to see nearby centers.</p>
            <div class="map-shell mt-4">
                <iframe id="facilityMap" title="India e-waste map" src="{{ $coverageStats['map_embed_url'] }}" loading="lazy"></iframe>
            </div>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Top matches</span>
                    <h2>Recommended centers</h2>
                </div>
                <button id="findFacilityBtn" class="eco-button eco-button-secondary" type="button"><i data-lucide="locate-fixed"></i><span>Live</span></button>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button class="filter-chip active" type="button" data-facility-filter="all">All</button>
                <button class="filter-chip" type="button" data-facility-filter="pickup_available">Pickup</button>
                <button class="filter-chip" type="button" data-facility-filter="data_wipe">Data wipe</button>
                <button class="filter-chip" type="button" data-facility-filter="battery_handling">Battery-safe</button>
            </div>
            <div id="facilityResults" class="mt-4 grid gap-3"></div>
        </article>
    </section>

    <section class="mt-5 grid gap-4 md:grid-cols-3">
        <article class="metric-panel"><span>E-waste recycled</span><strong><span data-dashboard="totals.ewaste_kg">0</span> kg</strong></article>
        <article class="metric-panel"><span>CO2 reduced</span><strong><span data-dashboard="totals.co2_kg">0</span> kg</strong></article>
        <article class="metric-panel"><span>Certificates</span><strong data-dashboard="totals.devices">0</strong></article>
    </section>

    <section class="mt-5 surface p-4 sm:p-5">
        <div class="section-head">
            <div>
                <span class="eyebrow">Certificate</span>
                <h2>Latest proof</h2>
            </div>
        </div>
        <div id="certificatePanel" class="certificate-panel mt-4">
            <p>No certificate yet.</p>
            <small>Analyze a device, record disposal, and download QR proof.</small>
        </div>
    </section>

    @include('sustainability.partials.analysis-modal')
@endsection
