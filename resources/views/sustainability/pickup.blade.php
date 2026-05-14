@extends('layouts.app')

@section('title', 'Plan Pickup')

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
        <span class="eyebrow">Pickup</span>
        <h1>Plan a home, office, or society pickup.</h1>
        <p>Get a pickup preview with center match and checklist.</p>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
        <article class="surface p-4 sm:p-5">
            <form id="pickupForm" class="grid gap-3">
                <label class="field">
                    <span>Device</span>
                    <input name="model_name" type="text" placeholder="Laptop, TV, phone, power bank">
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="field">
                        <span>City</span>
                        <select name="city">
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
                    <span>Preferred time</span>
                    <select name="preferred_window">
                        <option value="Tomorrow morning">Tomorrow morning</option>
                        <option value="Tomorrow evening">Tomorrow evening</option>
                        <option value="This weekend">This weekend</option>
                        <option value="Society drive">Society drive</option>
                    </select>
                </label>
                <button class="eco-button eco-button-primary justify-center" type="submit">
                    <i data-lucide="calendar-check"></i>
                    <span>Plan Pickup</span>
                </button>
            </form>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Preview</span>
                    <h2>Pickup plan</h2>
                </div>
                <span class="icon-badge blue"><i data-lucide="truck"></i></span>
            </div>
            <div id="pickupResult" class="pickup-result mt-4">
                <strong>Ready to plan</strong>
                <p>Submit your details to see the best center, point preview, and checklist.</p>
            </div>
        </article>
    </section>
@endsection
