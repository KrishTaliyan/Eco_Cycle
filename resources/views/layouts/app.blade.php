<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EcoCycle Smart India')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-zinc-950 antialiased">
    @yield('page_data')
    <div class="india-ribbon" aria-hidden="true"></div>
    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <header class="site-header">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('sustainability.index') }}" class="flex items-center gap-3" aria-label="EcoCycle Smart India home">
                <span class="brand-mark"><i data-lucide="recycle"></i></span>
                <span>
                    <span class="block text-base font-semibold text-zinc-950">EcoCycle Smart</span>
                    <span class="block text-xs text-zinc-500">India e-waste platform</span>
                </span>
            </a>

            <nav class="desktop-nav" aria-label="Primary">
                <a class="nav-link {{ request()->routeIs('sustainability.index') ? 'active' : '' }}" href="{{ route('sustainability.index') }}">Home</a>
                <a class="nav-link {{ request()->routeIs('facilities') ? 'active' : '' }}" href="{{ route('facilities') }}">Facilities</a>
                <a class="nav-link {{ request()->routeIs('pickup') ? 'active' : '' }}" href="{{ route('pickup') }}">Pickup</a>
                <a class="nav-link {{ request()->routeIs('learn') ? 'active' : '' }}" href="{{ route('learn') }}">Learn</a>
                <a class="nav-link {{ request()->routeIs('rewards') ? 'active' : '' }}" href="{{ route('rewards') }}">Rewards</a>
                <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a>
            </nav>

            <div class="hidden items-center gap-2 sm:flex">
                @auth
                    <span class="user-pill">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="eco-button eco-button-secondary" type="submit">Logout</button>
                    </form>
                @else
                    <a class="eco-button eco-button-secondary" href="{{ route('login') }}">Login</a>
                    <a class="eco-button eco-button-primary" href="{{ route('register') }}">Sign up</a>
                @endauth
            </div>
        </div>

        <nav class="mobile-nav" aria-label="Mobile primary">
            <a href="{{ route('sustainability.index') }}">Home</a>
            <a href="{{ route('facilities') }}">Facilities</a>
            <a href="{{ route('pickup') }}">Pickup</a>
            <a href="{{ route('learn') }}">Learn</a>
            <a href="{{ route('rewards') }}">Rewards</a>
            @guest
                <a href="{{ route('login') }}">Login</a>
            @endguest
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="notice-success">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr_1fr] lg:px-8">
            <div>
                <div class="flex items-center gap-3">
                    <span class="brand-mark"><i data-lucide="recycle"></i></span>
                    <strong>EcoCycle Smart India</strong>
                </div>
                <p class="mt-3 max-w-sm text-sm leading-6 text-zinc-600">A clean customer platform for finding e-waste centers, planning pickups, learning safe disposal, and earning eco rewards.</p>
            </div>
            <div>
                <h3>Platform</h3>
                <a href="{{ route('facilities') }}">Find centers</a>
                <a href="{{ route('pickup') }}">Plan pickup</a>
                <a href="{{ route('rewards') }}">Rewards</a>
            </div>
            <div>
                <h3>Company</h3>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('learn') }}">Awareness</a>
                <a href="{{ route('contact') }}">Contact</a>
            </div>
            <div>
                <h3>Trust</h3>
                <span>QR certificates</span>
                <span>India facility map</span>
                <span>Data wipe guidance</span>
            </div>
        </div>
    </footer>
</body>
</html>
