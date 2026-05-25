@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <div class="auth-badge"><i data-lucide="lock-keyhole"></i><span>Secure reset</span></div>
    <h1>Create a stronger password</h1>
    <p>Use at least eight characters with mixed case and a number.</p>

    <form class="auth-form" method="POST" action="{{ route('password.update') }}" data-validate-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="field">
            <span>Email address</span>
            <input name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field password-field">
            <span>Password</span>
            <input name="password" type="password" required autocomplete="new-password">
            <button class="password-toggle" type="button" data-password-toggle aria-label="Show password"><i data-lucide="eye"></i></button>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field password-field">
            <span>Confirm password</span>
            <input name="password_confirmation" type="password" required autocomplete="new-password">
            <button class="password-toggle" type="button" data-password-toggle aria-label="Show password"><i data-lucide="eye"></i></button>
        </label>

        <button class="eco-button eco-button-primary auth-submit" type="submit">
            <i data-lucide="rotate-cw"></i>
            <span>Reset password</span>
        </button>
    </form>
@endsection
