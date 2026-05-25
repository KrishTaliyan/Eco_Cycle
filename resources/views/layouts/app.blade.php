<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings?->theme ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="EcoCycle Smart - scan devices, find collection centers, earn rewards for responsible e-waste disposal across India.">
    <title>@yield('title', 'EcoCycle Smart') - EcoCycle</title>
    <script>
        (function() {
            const stored = localStorage.getItem('ecocycle-theme');
            const base   = stored || document.documentElement.dataset.theme || 'light';
            const resolved = base === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : base;
            document.documentElement.dataset.theme = resolved;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $primaryNav = [
        ['label' => 'Home',       'route' => 'sustainability.index', 'icon' => 'home'],
        ['label' => 'Facilities', 'route' => 'facilities',           'icon' => 'map-pinned'],
        ['label' => 'Pickup',     'route' => 'pickup',               'icon' => 'truck'],
        ['label' => 'Rewards',    'route' => 'rewards',              'icon' => 'gift'],
        ['label' => 'Learn',      'route' => 'learn',                'icon' => 'book-open'],
    ];
    $workspaceNav = [
        ['label' => 'My Space',  'route' => 'dashboard',       'icon' => 'layout-dashboard', 'auth' => true],
        ['label' => 'Shop Ops',  'route' => 'shop.dashboard',  'icon' => 'store',            'shop' => true],
        ['label' => 'Profile',   'route' => 'profile',         'icon' => 'user-round',        'auth' => true],
        ['label' => 'Settings',  'route' => 'settings',        'icon' => 'settings',          'auth' => true],
        ['label' => 'Admin Ops', 'route' => 'admin.dashboard', 'icon' => 'shield',            'admin' => true],
        ['label' => 'About',     'route' => 'about',           'icon' => 'sparkles'],
        ['label' => 'Contact',   'route' => 'contact',         'icon' => 'message-circle'],
    ];
@endphp

@yield('page_data')

{{-- Toast --}}
<div id="toast" class="toast" role="status" aria-live="polite"></div>

{{-- Sidebar backdrop --}}
<div class="side-nav-backdrop" data-sidebar-close hidden></div>

{{-- Sidebar --}}
<aside class="side-nav" aria-label="Primary navigation">
    <div class="side-nav-top">
        <a href="{{ route('sustainability.index') }}" class="brand-lockup" aria-label="EcoCycle Smart home">
            <span class="brand-mark"><i data-lucide="recycle"></i></span>
            <span>
                <span class="brand-title">EcoCycle</span>
                <span class="brand-subtitle">Smart recycling</span>
            </span>
        </a>
        <button class="icon-button side-nav-close" type="button" data-sidebar-close aria-label="Close navigation">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="side-nav-section">
        <span>Main</span>
        @foreach ($primaryNav as $item)
            <a class="side-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i data-lucide="{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="side-nav-section">
        <span>Workspace</span>
        @foreach ($workspaceNav as $item)
            @continue(($item['auth'] ?? false) && ! auth()->check())
            @continue(($item['shop'] ?? false) && (! auth()->check() || ! auth()->user()->hasRole(['shop_owner','admin'])))
            @continue(($item['admin'] ?? false) && (! auth()->check() || ! auth()->user()->hasRole('admin')))
            <a class="side-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i data-lucide="{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="side-nav-card">
        <span>Quick action</span>
        <strong>Scan and earn</strong>
        <a href="{{ (auth()->check() ? route('dashboard') : route('sustainability.index')) . '#deviceForm' }}">
            <i data-lucide="scan-line"></i>
            <span>Start</span>
        </a>
    </div>
</aside>

{{-- Header --}}
<header class="site-header">
    <div>
        <div class="brand-area">
            <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open navigation" id="sidebarToggle">
                <i data-lucide="menu"></i>
            </button>
            <a href="{{ route('sustainability.index') }}" class="brand-lockup" aria-label="EcoCycle Smart home">
                <span class="brand-mark"><i data-lucide="recycle"></i></span>
                <span>
                    <span class="brand-title">EcoCycle Smart</span>
                    <span class="brand-subtitle">E-waste made simple</span>
                </span>
            </a>
        </div>

        <div class="nav-actions">
            <button class="icon-button mobile-search-trigger" type="button" data-search-open aria-label="Search">
                <i data-lucide="search"></i>
            </button>
            <button class="search-trigger" type="button" data-search-open>
                <i data-lucide="search"></i>
                <span>Search</span>
                <kbd>Ctrl K</kbd>
            </button>
            <button class="icon-button" type="button" data-theme-toggle aria-label="Toggle theme">
                <i data-lucide="sun-moon"></i>
            </button>

            @auth
                <a class="notification-button" href="{{ route('dashboard') }}#notifications" aria-label="Notifications">
                    <i data-lucide="bell"></i>
                    @php($unread = auth()->user()->notifications()->unread()->count())
                    @if ($unread > 0)
                        <span>{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <details class="account-menu">
                    <summary>
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </summary>
                    <div>
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->email }}</small>
                        <span class="account-role">{{ auth()->user()->roleLabel() }}</span>
                        <a href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i> My Space</a>
                        <a href="{{ route('profile') }}"><i data-lucide="user-round"></i> Profile</a>
                        <a href="{{ route('settings') }}"><i data-lucide="settings"></i> Settings</a>
                        @if (auth()->user()->hasRole(['shop_owner', 'admin']))
                            <a href="{{ route('shop.dashboard') }}"><i data-lucide="store"></i> Shop Ops</a>
                        @endif
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('admin.dashboard') }}"><i data-lucide="shield"></i> Admin Ops</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i data-lucide="log-out"></i> Sign out</button>
                        </form>
                    </div>
                </details>
            @else
                <a class="eco-button eco-button-secondary hide-small" href="{{ route('login') }}">
                    <i data-lucide="log-in"></i><span>Login</span>
                </a>
                <a class="eco-button eco-button-primary hide-small" href="{{ route('register') }}">
                    <i data-lucide="user-plus"></i><span>Sign up</span>
                </a>
            @endauth
        </div>
    </div>
</header>

{{-- App shell --}}
<div class="app-shell">
    <div class="shell-content">
        <main class="app-main">
            @if (session('status'))
                <div class="notice-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

{{-- Global search --}}
<div id="globalSearch" class="search-backdrop" hidden role="dialog" aria-modal="true" aria-label="Search EcoCycle">
    <section class="search-panel">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input id="globalSearchInput" type="search" placeholder="Search facilities, devices, rewards..." autocomplete="off">
            <button class="icon-button" type="button" data-search-close aria-label="Close search">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div id="globalSearchResults" class="search-results">
            <div class="empty-state">Start typing to search.</div>
        </div>
    </section>
</div>

{{-- Footer --}}
<footer class="site-footer">
    <div>
        <div>
            <div class="brand-lockup">
                <span class="brand-mark"><i data-lucide="recycle"></i></span>
                <strong style="font-family:var(--font-display);font-weight:800;">EcoCycle Smart</strong>
            </div>
            <p>Simple tools for responsible electronics recycling across India.</p>
            <p style="font-size:.75rem;color:var(--app-muted);margin-top:.5rem;">
                Aligned with Indian e-waste rules.
            </p>
        </div>
        <div>
            <h3>Platform</h3>
            <a href="{{ route('sustainability.index') }}">Home</a>
            <a href="{{ route('facilities') }}">Facilities</a>
            <a href="{{ route('pickup') }}">Pickup</a>
            <a href="{{ route('rewards') }}">Rewards</a>
        </div>
        <div>
            <h3>Account</h3>
            @auth
                <a href="{{ route('dashboard') }}">My Space</a>
                <a href="{{ route('profile') }}">Profile</a>
                <a href="{{ route('settings') }}">Settings</a>
            @else
                <a href="{{ route('login') }}">Sign in</a>
                <a href="{{ route('register') }}">Create account</a>
            @endauth
            <a href="{{ route('learn') }}">Learn</a>
        </div>
        <div>
            <h3>Support</h3>
            <a href="{{ route('about') }}">About</a>
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('terms') }}">Terms of use</a>
        </div>
    </div>
    <div style="max-width:var(--content-max);margin:0 auto;padding:.875rem 1.5rem;border-top:1px solid var(--app-border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;font-size:.8125rem;color:var(--app-muted);">
        <span>&copy; {{ date('Y') }} EcoCycle Smart. Responsible recycling platform.</span>
        <span>Made for India</span>
    </div>
</footer>

</body>
</html>
