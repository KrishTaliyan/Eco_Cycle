@extends(auth()->user()?->hasRole('admin') ? 'layouts.admin' : 'layouts.app')

@section('title', 'Profile')

@section('content')
    <section class="page-hero compact home-reveal">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Profile</span>
                <h1>Your account.</h1>
                <p>Keep details and notifications simple.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="eco-button eco-button-secondary" type="submit"><i data-lucide="log-out"></i><span>Sign out</span></button>
            </form>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1fr_0.75fr]">
        <form class="surface p-4 sm:p-5" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="section-head">
                <div><span class="eyebrow">Edit profile</span><h2>User info</h2></div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="field"><span>Name</span><input name="name" value="{{ old('name', $user->name) }}" required></label>
                <label class="field"><span>Email</span><input name="email" type="email" value="{{ old('email', $user->email) }}" required></label>
                <label class="field"><span>Phone</span><input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+91 90000 00000"></label>
                <label class="field"><span>Organization</span><input name="organization" value="{{ old('organization', $user->organization) }}" placeholder="Optional"></label>
                <label class="field sm:col-span-2"><span>Avatar</span><input name="avatar" type="file" accept="image/*"></label>
            </div>

            @if ($errors->any())
                <div class="form-alert error mt-4">{{ $errors->first() }}</div>
            @endif

            <button class="eco-button eco-button-primary mt-5" type="submit"><i data-lucide="save"></i><span>Save</span></button>
        </form>

        <aside class="surface p-4 sm:p-5">
            <div class="profile-card">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="">
                @else
                    <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <div class="mt-5 grid gap-3">
                <a class="toggle-row" href="{{ route('settings') }}">
                    <span>Notifications</span>
                    <i data-lucide="chevron-right"></i>
                </a>
                <div class="mini-stat p-3"><span>Status</span><strong>{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</strong></div>
            </div>
        </aside>
    </section>
@endsection
