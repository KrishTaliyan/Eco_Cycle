@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <div class="auth-badge"><i data-lucide="mail-check"></i><span>Password recovery</span></div>
    <h1>Get a secure reset link</h1>
    <p>Enter the email on your EcoCycle account and we will send a time-limited password reset link.</p>

    @if (session('status'))
        <div class="notice-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('password.email') }}" data-validate-form>
        @csrf
        <label class="field">
            <span>Email address</span>
            <input name="email" type="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <button class="eco-button eco-button-primary auth-submit" type="submit">
            <i data-lucide="send"></i>
            <span>Send reset link</span>
        </button>
    </form>

    <div class="auth-footer">
        Remembered it?
        <a href="{{ route('login') }}">Back to login</a>
    </div>
@endsection
