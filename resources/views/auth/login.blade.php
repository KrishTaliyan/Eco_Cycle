@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <section class="auth-shell">
        <article class="auth-card">
            <span class="eyebrow">Welcome back</span>
            <h1>Login to your wallet</h1>
            <p>Track points, certificates, pickups, and recycling progress.</p>

            <form class="mt-5 grid gap-3" method="POST" action="{{ route('login.store') }}">
                @csrf
                <label class="field">
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input name="password" type="password" required>
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-600">
                    <input name="remember" type="checkbox" value="1">
                    Remember me
                </label>
                @if ($errors->any())
                    <p class="text-sm font-medium text-rose-700">{{ $errors->first() }}</p>
                @endif
                <button class="eco-button eco-button-primary justify-center" type="submit">Login</button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">New here? <a class="font-semibold text-emerald-700" href="{{ route('register') }}">Create account</a></p>
        </article>
    </section>
@endsection
