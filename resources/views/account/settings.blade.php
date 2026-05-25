@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <section class="page-hero compact">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Workspace settings</span>
                <h1>Preferences.</h1>
                <p>Theme, density, timezone, and notifications.</p>
            </div>
            <a class="eco-button eco-button-secondary" href="{{ route('profile') }}"><i data-lucide="user-round"></i><span>Profile</span></a>
        </div>
    </section>

    @php($settings = $user->settings)
    @php($channels = $settings?->notification_channels ?? [])

    <form class="surface p-4 sm:p-5" method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
        <div class="section-head">
            <div><span class="eyebrow">Preferences</span><h2>Interface and notifications</h2></div>
            <span class="icon-badge purple"><i data-lucide="sliders-horizontal"></i></span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="field">
                <span>Theme</span>
                <select name="theme" data-theme-select>
                    @foreach (['system' => 'System', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                        <option value="{{ $value }}" @selected(($settings->theme ?? 'system') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Density</span>
                <select name="density">
                    <option value="comfortable" @selected(($settings->density ?? 'comfortable') === 'comfortable')>Comfortable</option>
                    <option value="compact" @selected(($settings->density ?? 'comfortable') === 'compact')>Compact</option>
                </select>
            </label>
            <label class="field">
                <span>Timezone</span>
                <input name="timezone" value="{{ old('timezone', $settings->timezone ?? 'Asia/Kolkata') }}" required>
            </label>
            <label class="field">
                <span>Locale</span>
                <input name="locale" value="{{ old('locale', $settings->locale ?? 'en') }}" required>
            </label>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <label class="toggle-row"><input name="email_notifications" type="checkbox" value="1" @checked($channels['email'] ?? true)><span>Email notifications</span></label>
            <label class="toggle-row"><input name="product_notifications" type="checkbox" value="1" @checked($channels['product'] ?? true)><span>Product alerts</span></label>
            <label class="toggle-row"><input name="community_updates" type="checkbox" value="1" @checked($channels['community'] ?? false)><span>Community updates</span></label>
        </div>

        @if ($errors->any())
            <div class="form-alert error mt-4">{{ $errors->first() }}</div>
        @endif

        <button class="eco-button eco-button-primary mt-5" type="submit"><i data-lucide="save"></i><span>Save settings</span></button>
    </form>
@endsection
