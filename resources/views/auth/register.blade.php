@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <section class="auth-shell">
        <article class="auth-card">
            <span class="eyebrow">Create account</span>
            <h1>Start earning eco points</h1>
            <p>Save your recycling activity, rewards, and certificates.</p>

            <form class="mt-5 grid gap-3" method="POST" action="{{ route('register.store') }}">
                @csrf
                <label class="field">
                    <span>Name</span>
                    <input name="name" value="{{ old('name') }}" required autofocus>
                </label>
                <label class="field">
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" required>
                </label>
                <label class="field">
                    <span>Password</span>
                    <input name="password" type="password" required>
                </label>
                <label class="field">
                    <span>Confirm password</span>
                    <input name="password_confirmation" type="password" required>
                </label>
                @if ($errors->any())
                    <p class="text-sm font-medium text-rose-700">{{ $errors->first() }}</p>
                @endif
                <button class="eco-button eco-button-primary justify-center" type="submit">Create account</button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">Already registered? <a class="font-semibold text-emerald-700" href="{{ route('login') }}">Login</a></p>
        </article>
    </section>
@endsection
