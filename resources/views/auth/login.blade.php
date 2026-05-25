@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1>Welcome back</h1>
    <p>Sign in to your recycling workspace.</p>

    @if (session('status'))
        <div class="notice-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('login.store') }}" data-validate-form>
        @csrf

        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field password-field">
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
            <button class="password-toggle" type="button" data-password-toggle aria-label="Show password"><i data-lucide="eye"></i></button>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <div class="auth-options">
            <label class="check-label">
                <input type="checkbox" name="remember">
                <span>Stay signed in</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button class="eco-button eco-button-primary auth-submit" type="submit">
            <i data-lucide="log-in"></i>
            <span>Sign in</span>
        </button>
    </form>

    @if (config('services.demo_login.enabled'))
        <div class="demo-card">
            <p>Try the full platform without signing up.</p>
            <form method="POST" action="{{ route('login.demo') }}">
                @csrf
                <button class="eco-button eco-button-secondary auth-submit" type="submit">
                    <i data-lucide="play-circle"></i>
                    <span>Launch demo workspace</span>
                </button>
            </form>
        </div>
    @endif

    <div class="auth-footer">
        Don't have an account?
        <a href="{{ route('register') }}">Create one free</a>
    </div>
@endsection
