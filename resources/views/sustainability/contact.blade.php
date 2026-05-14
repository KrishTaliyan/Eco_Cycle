@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">Contact</span>
        <h1>Need help with recycling?</h1>
        <p>Send a message for pickup, society drives, school programs, or business disposal.</p>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
        <article class="surface p-4 sm:p-5">
            <h2 class="text-xl font-semibold">Support</h2>
            <div class="mt-4 grid gap-3">
                <div class="contact-row"><i data-lucide="mail"></i><span>support@ecocycle.local</span></div>
                <div class="contact-row"><i data-lucide="phone"></i><span>+91 1800 000 3939</span></div>
                <div class="contact-row"><i data-lucide="clock"></i><span>Mon-Sat, 9:30 AM to 6:30 PM</span></div>
            </div>
        </article>

        <form class="surface p-4 sm:p-5" method="POST" action="{{ route('contact.submit') }}">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="field"><span>Name</span><input name="name" value="{{ old('name') }}" required></label>
                <label class="field"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required></label>
            </div>
            <label class="field mt-3"><span>Message</span><textarea name="message" rows="5" required>{{ old('message') }}</textarea></label>
            @if ($errors->any())
                <p class="mt-3 text-sm font-medium text-rose-700">{{ $errors->first() }}</p>
            @endif
            <button class="eco-button eco-button-primary mt-4" type="submit">Send message</button>
        </form>
    </section>
@endsection
