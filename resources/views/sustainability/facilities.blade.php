@extends('layouts.app')

@section('title', 'Facilities')

@section('page_data')
    <script>
        window.ecoInitial = {
            cityPresets: @json($cityPresets),
            coverageStats: @json($coverageStats),
        };
    </script>
@endsection

@section('content')
    <section class="page-hero compact home-reveal">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Facilities</span>
                <h1>Find a trusted center.</h1>
                <p>Search by city or use your location.</p>
            </div>
            <button id="findFacilityBtn" class="eco-button eco-button-primary" type="button">
                <i data-lucide="locate-fixed"></i>
                <span>Use Location</span>
            </button>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.72fr_1.28fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Search</span><h2>Choose city</h2></div>
            </div>

            <div class="mt-4 grid gap-3">
                <label class="field">
                    <span>City</span>
                    <select id="citySelect">
                        @foreach ($cityPresets as $city)
                            <option value="{{ $city['city'] }}">{{ $city['city'] }}</option>
                        @endforeach
                    </select>
                </label>
                <button id="useCityBtn" class="eco-button eco-button-primary" type="button">
                    <i data-lucide="search"></i>
                    <span>Search</span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2" aria-label="Quick cities">
                @foreach (array_slice($cityPresets, 0, 8) as $city)
                    <button class="city-chip" type="button" data-city="{{ $city['city'] }}" data-lat="{{ $city['lat'] }}" data-lng="{{ $city['lng'] }}">
                        {{ $city['city'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-2" aria-label="Facility filters">
                <button class="filter-chip active" type="button" data-facility-filter="all">All</button>
                <button class="filter-chip" type="button" data-facility-filter="pickup_available">Pickup</button>
                <button class="filter-chip" type="button" data-facility-filter="certificate_supported">Proof</button>
                <button class="filter-chip" type="button" data-facility-filter="data_wipe">Data wipe</button>
                <button class="filter-chip" type="button" data-facility-filter="battery_handling">Battery</button>
            </div>

            <p id="locationStatus" class="mt-4 text-sm font-bold" style="color: var(--app-muted)">Select a city to load centers.</p>
        </article>

        <article class="surface overflow-hidden">
            <div class="map-shell india-map-shell rounded-none border-0">
                <iframe id="facilityMap" title="E-waste facility map" src="{{ $coverageStats['map_embed_url'] }}" loading="lazy"></iframe>
            </div>
            <div class="grid grid-cols-3 border-t text-center text-xs" style="border-color: var(--app-border); background: var(--app-card-soft)">
                <div class="map-stat"><strong>{{ $coverageStats['centers'] }}</strong><span>Centers</span></div>
                <div class="map-stat"><strong>{{ $coverageStats['states'] }}</strong><span>States</span></div>
                <div class="map-stat"><strong>{{ $coverageStats['pickup_enabled'] }}</strong><span>Pickup</span></div>
            </div>
        </article>
    </section>

    <section class="section-band">
        <div class="section-head">
            <div><span class="eyebrow">Results</span><h2>Nearby centers</h2></div>
        </div>
        <div id="facilityResults" class="mt-4 grid gap-3 lg:grid-cols-2"></div>
    </section>
@endsection
