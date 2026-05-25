@extends('layouts.auth')

@section('title', 'Verify email')

@section('content')
    <div class="auth-badge"><i data-lucide="badge-check"></i><span>Email verification</span></div>
    <h1>Verify your email</h1>
    <p>Enter the six-digit code sent to {{ auth()->user()->email }}.</p>

    @if (session('status'))
        <div class="notice-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('verification.otp.store') }}" data-validate-form>
        @csrf
        <label class="field otp-field">
            <span>Verification code</span>
            <input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus data-otp-input>
            @error('code') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <button class="eco-button eco-button-primary auth-submit" type="submit">
            <i data-lucide="check-circle"></i>
            <span>Verify account</span>
        </button>
    </form>

    <form class="auth-secondary-form" method="POST" action="{{ route('verification.otp.resend') }}">
        @csrf
        <button class="eco-button eco-button-secondary auth-submit" type="submit">
            <i data-lucide="refresh-cw"></i>
            <span>Resend code</span>
        </button>
    </form>
@endsection
