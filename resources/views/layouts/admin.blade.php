<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings?->theme ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="EcoCycle admin console for platform operations.">
    <title>@yield('title', 'Admin') - EcoCycle Admin</title>
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
<body class="admin-body">
@php
    $adminNav = [
        ['label' => 'Overview', 'href' => route('admin.dashboard'), 'icon' => 'layout-dashboard'],
        ['label' => 'Requests', 'href' => route('admin.dashboard').'#requests', 'icon' => 'clipboard-check'],
        ['label' => 'Users', 'href' => route('admin.dashboard').'#users', 'icon' => 'users'],
        ['label' => 'Centers', 'href' => route('admin.dashboard').'#centers', 'icon' => 'building2'],
        ['label' => 'Audit', 'href' => route('admin.dashboard').'#audit', 'icon' => 'shield-check'],
    ];
    $adminUser = auth()->user();
@endphp

@yield('page_data')

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<div class="side-nav-backdrop" data-sidebar-close hidden></div>

<aside class="side-nav admin-side-nav" aria-label="Admin navigation">
    <div class="side-nav-top">
        <a href="{{ route('admin.dashboard') }}" class="brand-lockup" aria-label="EcoCycle admin home">
            <span class="brand-mark admin-mark"><i data-lucide="shield-check"></i></span>
            <span>
                <span class="brand-title">EcoCycle Admin</span>
                <span class="brand-subtitle">Operations console</span>
            </span>
        </a>
        <button class="icon-button side-nav-close" type="button" data-sidebar-close aria-label="Close navigation">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="side-nav-section">
        <span>Command</span>
        @foreach ($adminNav as $item)
            <a class="side-link {{ request()->routeIs('admin.dashboard') && $loop->first ? 'active' : '' }}" href="{{ $item['href'] }}">
                <i data-lucide="{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="side-nav-section">
        <span>Account</span>
        <a class="side-link {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">
            <i data-lucide="user-round"></i>
            <span>Profile</span>
        </a>
        <a class="side-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">
            <i data-lucide="settings"></i>
            <span>Settings</span>
        </a>
    </div>

    <div class="side-nav-card admin-identity-card">
        <span>Signed in</span>
        <strong>{{ $adminUser?->name }}</strong>
        <p>{{ $adminUser?->email }}</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i data-lucide="log-out"></i><span>Sign out</span></button>
        </form>
    </div>
</aside>

<header class="site-header admin-header">
    <div>
        <div class="brand-area">
            <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open navigation" id="sidebarToggle">
                <i data-lucide="menu"></i>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="brand-lockup" aria-label="EcoCycle admin dashboard">
                <span class="brand-mark admin-mark"><i data-lucide="shield-check"></i></span>
                <span>
                    <span class="brand-title">Admin Console</span>
                    <span class="brand-subtitle">Platform authority</span>
                </span>
            </a>
        </div>

        <div class="nav-actions">
            <button class="icon-button" type="button" data-theme-toggle aria-label="Toggle theme">
                <i data-lucide="sun-moon"></i>
            </button>
            <details class="account-menu">
                <summary>
                    @if ($adminUser?->avatar_url)
                        <img src="{{ $adminUser->avatar_url }}" alt="{{ $adminUser->name }}">
                    @else
                        {{ strtoupper(substr($adminUser?->name ?? 'A', 0, 1)) }}
                    @endif
                </summary>
                <div>
                    <strong>{{ $adminUser?->name }}</strong>
                    <small>{{ $adminUser?->email }}</small>
                    <span class="account-role">Admin</span>
                    <a href="{{ route('admin.dashboard') }}"><i data-lucide="layout-dashboard"></i> Console</a>
                    <a href="{{ route('profile') }}"><i data-lucide="user-round"></i> Profile</a>
                    <a href="{{ route('settings') }}"><i data-lucide="settings"></i> Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"><i data-lucide="log-out"></i> Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>

<div class="app-shell admin-app-shell">
    <div class="shell-content">
        <main class="app-main">
            @if (session('status'))
                <div class="notice-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
