@extends('layouts.app')

@section('title', 'Pickup')

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
                <span class="eyebrow">Pickup</span>
                <h1>Schedule a pickup.</h1>
                <p>A guided flow for first-time recyclers.</p>
            </div>
            <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}">
                <i data-lucide="map-pin"></i>
                <span>Find centers</span>
            </a>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
        <article class="surface p-4 sm:p-5">
            <div class="timeline">
                <div class="timeline-step"><span>1</span><strong>Device</strong></div>
                <div class="timeline-step"><span>2</span><strong>Address</strong></div>
                <div class="timeline-step"><span>3</span><strong>Date</strong></div>
                <div class="timeline-step"><span>4</span><strong>Confirm</strong></div>
            </div>

            <form id="pickupForm" class="mt-5 grid gap-4">
                <label class="field">
                    <span>Select device</span>
                    <input name="model_name" type="text" placeholder="Laptop, phone, TV" required>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="field">
                        <span>City</span>
                        <select name="city" required>
                            @foreach ($cityPresets as $city)
                                <option value="{{ $city['city'] }}">{{ $city['city'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Pincode</span>
                        <input name="pincode" inputmode="numeric" maxlength="6" placeholder="400001">
                    </label>
                </div>

                <label class="field">
                    <span>Pick date</span>
                    <select name="preferred_window" required>
                        <option value="Tomorrow morning">Tomorrow morning</option>
                        <option value="Tomorrow evening">Tomorrow evening</option>
                        <option value="This weekend">This weekend</option>
                        <option value="Society drive">Society drive</option>
                    </select>
                </label>

                <button class="eco-button eco-button-primary" type="submit">
                    <i data-lucide="calendar-check"></i>
                    <span>Confirm Pickup</span>
                </button>
            </form>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Status</span><h2>Pickup plan</h2></div>
                <span class="icon-badge"><i data-lucide="truck"></i></span>
            </div>
            <div id="pickupResult" class="pickup-result mt-4">
                <strong>Ready</strong>
                <p>Complete the steps to see your pickup summary.</p>
            </div>
        </article>
    </section>
@endsection
