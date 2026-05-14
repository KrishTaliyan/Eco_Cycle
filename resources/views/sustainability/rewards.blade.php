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
    <section class="page-hero compact">
        <span class="eyebrow">Rewards</span>
        <h1>Earn points for responsible recycling.</h1>
        <p>Badges, coupons, streaks, leaderboards, and certificates.</p>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="metric-panel"><span>Your points</span><strong data-dashboard="user.points">0</strong></article>
        <article class="metric-panel"><span>Devices recycled</span><strong data-dashboard="user.devices">0</strong></article>
        <article class="metric-panel"><span>CO2 reduced</span><strong><span data-dashboard="user.co2_kg">0</span> kg</strong></article>
    </section>

    <section class="mt-4 grid gap-4 lg:grid-cols-2">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Wallet</span><h2>Badges and coupons</h2></div></div>
            <div id="badges" class="mt-4 grid gap-2"></div>
            <div id="coupons" class="mt-4 grid gap-2"></div>
        </article>
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Challenges</span><h2>Daily actions</h2></div></div>
            <div id="challenges" class="mt-4 grid gap-3"></div>
        </article>
    </section>

    <section class="mt-4 grid gap-4 lg:grid-cols-2">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Community</span><h2>Leaderboard</h2></div></div>
            <div id="leaderboard" class="mt-4 grid gap-2"></div>
        </article>
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">States</span><h2>India ranking</h2></div></div>
            <div id="stateRanking" class="mt-4 grid gap-2"></div>
        </article>
    </section>
@endsection
