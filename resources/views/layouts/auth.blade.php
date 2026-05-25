<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->settings?->theme ?? 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="EcoCycle Smart account access for recycling, pickups, rewards, and proof records.">
    <title>@yield('title', 'Account') - EcoCycle Smart</title>
    <script>
        (function() {
            const stored = localStorage.getItem('ecocycle-theme');
            const base = stored || document.documentElement.dataset.theme || 'light';
            const resolved = base === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : base;
            document.documentElement.dataset.theme = resolved;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
<div id="toast" class="toast" role="status" aria-live="polite"></div>

<main class="auth-shell">
    <article class="auth-card @yield('auth_card_class')">
        <a href="{{ route('sustainability.index') }}" class="brand-lockup auth-brand" aria-label="EcoCycle Smart home">
            <span class="brand-mark"><i data-lucide="recycle"></i></span>
            <span>
                <span class="brand-title">EcoCycle Smart</span>
                <span class="brand-subtitle">E-waste made simple</span>
            </span>
        </a>

        @yield('content')
    </article>
</main>
</body>
</html>
