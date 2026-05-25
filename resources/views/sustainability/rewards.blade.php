@extends('layouts.app')

@section('title', 'Rewards')

@section('page_data')
    <script>
        window.ecoInitial = {
            dashboard: @json($dashboard),
        };
    </script>
@endsection

@section('content')
    <section class="page-hero compact home-reveal">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Rewards</span>
                <h1>Earn as you recycle.</h1>
                <p>Points, badges, and recent rewards in one place.</p>
            </div>
            <a class="eco-button eco-button-primary" href="{{ route('pickup') }}">
                <i data-lucide="truck"></i>
                <span>Schedule Pickup</span>
            </a>
        </div>
    </section>

    <section class="dashboard-metrics">
        <article class="metric-panel"><span>Total points</span><strong data-dashboard="user.points">0</strong></article>
        <article class="metric-panel"><span>Badges</span><strong>4</strong></article>
        <article class="metric-panel"><span>Streak</span><strong><span data-dashboard="user.streak_days">0</span> days</strong></article>
        <article class="metric-panel"><span>CO2 saved</span><strong><span data-dashboard="user.co2_kg">0</span> kg</strong></article>
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Progress</span><h2>Next badge</h2></div></div>
            <div class="mt-5">
                <div class="flex items-center justify-between gap-3">
                    <strong>Eco Champion</strong>
                    <span class="reward-pill">64%</span>
                </div>
                <div class="reward-progress mt-4"><span style="width: 64%"></span></div>
            </div>
            <div id="badges" class="mt-5 grid gap-2"></div>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Recent rewards</span><h2>Unlocked</h2></div></div>
            <div id="coupons" class="mt-4 grid gap-2"></div>
            <div id="challenges" class="mt-4 grid gap-3"></div>
        </article>
    </section>
@endsection
