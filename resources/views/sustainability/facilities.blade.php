@extends('layouts.app')

@section('title', 'Find E-Waste Facilities')

@section('page_data')
    <script>
        window.ecoInitial = {
            cityPresets: @json($cityPresets),
            coverageStats: @json($coverageStats),
        };
    </script>
@endsection

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">Facilities</span>
        <h1>Find an e-waste center near you.</h1>
        <p>Search Indian cities, filter services, and open directions.</p>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Search</span>
                    <h2>Choose location</h2>
                </div>
                <button id="findFacilityBtn" class="eco-button eco-button-secondary" type="button">
                    <i data-lucide="locate-fixed"></i>
                    <span>Live location</span>
                </button>
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
                <button id="useCityBtn" class="eco-button eco-button-primary self-end" type="button">Search</button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (array_slice($cityPresets, 0, 10) as $city)
                    <button class="city-chip" type="button" data-city="{{ $city['city'] }}" data-lat="{{ $city['lat'] }}" data-lng="{{ $city['lng'] }}">{{ $city['city'] }}</button>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button class="filter-chip active" type="button" data-facility-filter="all">All</button>
                <button class="filter-chip" type="button" data-facility-filter="pickup_available">Pickup</button>
                <button class="filter-chip" type="button" data-facility-filter="data_wipe">Data wipe</button>
                <button class="filter-chip" type="button" data-facility-filter="battery_handling">Battery</button>
                <button class="filter-chip" type="button" data-facility-filter="certificate_supported">Certificate</button>
            </div>

            <p id="locationStatus" class="mt-4 text-sm text-zinc-500">Select a city to start.</p>
        </article>

        <article class="surface overflow-hidden">
            <div class="map-shell india-map-shell">
                <iframe id="facilityMap" title="India e-waste facility map" src="{{ $coverageStats['map_embed_url'] }}" loading="lazy"></iframe>
            </div>
            <div class="grid grid-cols-3 border-b border-zinc-200 bg-zinc-50 text-center text-xs">
                <div class="map-stat"><strong>{{ $coverageStats['centers'] }}</strong><span>Centers</span></div>
                <div class="map-stat"><strong>{{ $coverageStats['states'] }}</strong><span>States</span></div>
                <div class="map-stat"><strong>{{ $coverageStats['pickup_enabled'] }}</strong><span>Pickup</span></div>
            </div>
        </article>
    </section>

    <section class="mt-4 surface p-4 sm:p-5">
        <div class="section-head">
            <div>
                <span class="eyebrow">Results</span>
                <h2>Best matches</h2>
            </div>
        </div>
        <div id="facilityResults" class="mt-4 grid gap-3 lg:grid-cols-2"></div>
    </section>
@endsection
