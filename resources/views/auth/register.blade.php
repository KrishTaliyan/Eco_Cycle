@extends('layouts.auth')

@section('title', 'Create account')
@section('auth_card_class', 'auth-card-wide')

@section('content')
    @php($roleOptions = collect(\App\Models\User::roleOptions())->only(['customer', 'shop_owner']))

    <h1>Create account</h1>
    <p>Choose the workspace that fits your role.</p>

    @if (session('status'))
        <div class="notice-success">{{ session('status') }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('register.store') }}" data-validate-form>
        @csrf

        <label class="field">
            <span>Full name</span>
            <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" autofocus required>
            @error('name') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label class="field">
            <span>Email address</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <div>
            <span class="field-label">Account type</span>
            <div class="role-picker">
                @foreach ($roleOptions as $role => $meta)
                    <label class="role-option">
                        <input type="radio" name="role" value="{{ $role }}" required @checked(old('role', 'customer') === $role)>
                        <span>
                            <i data-lucide="{{ $meta['icon'] }}"></i>
                            <strong>{{ $meta['label'] }}</strong>
                            <small>{{ $meta['description'] }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('role') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <label class="field">
            <span>Organization <em>optional</em></span>
            <input type="text" name="organization" value="{{ old('organization') }}" placeholder="Company, school, society...">
            @error('organization') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <div class="auth-split-grid">
            <label class="field password-field">
                <span>Password</span>
                <input type="password" name="password" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-label="Show password"><i data-lucide="eye"></i></button>
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </label>

            <label class="field password-field">
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-label="Show password"><i data-lucide="eye"></i></button>
            </label>
        </div>

        <button class="eco-button eco-button-primary auth-submit" type="submit">
            <i data-lucide="user-plus"></i>
            <span>Create account</span>
        </button>
    </form>

    <div class="auth-footer">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
    </div>
@endsection
