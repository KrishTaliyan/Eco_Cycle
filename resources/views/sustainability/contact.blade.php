@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    <section class="page-hero compact">
        <div class="page-hero-row">
            <div>
                <span class="eyebrow">Contact</span>
                <h1>Need help with recycling?</h1>
                <p>Send a message for pickup planning, facility support, certificates, or society and office collection drives.</p>
            </div>
            <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}">
                <i data-lucide="map-pin"></i>
                <span>Find centers</span>
            </a>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Support</span>
                    <h2>Contact details</h2>
                    <p>Use the form for requests that need a response.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3">
                <div class="contact-row"><i data-lucide="mail"></i><span>support@ecocycle.local</span></div>
                <div class="contact-row"><i data-lucide="phone"></i><span>+91 1800 000 3939</span></div>
                <div class="contact-row"><i data-lucide="clock"></i><span>Mon-Sat, 9:30 AM to 6:30 PM</span></div>
            </div>
        </article>

        <form class="surface p-4 sm:p-5" method="POST" action="{{ route('contact.submit') }}">
            @csrf
            <div class="section-head">
                <div>
                    <span class="eyebrow">Message</span>
                    <h2>Tell us what you need</h2>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="field"><span>Name</span><input name="name" value="{{ old('name') }}" required></label>
                <label class="field"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" required></label>
            </div>
            <label class="field mt-3"><span>Message</span><textarea name="message" rows="5" placeholder="Pickup request, certificate help, bulk drive support..." required>{{ old('message') }}</textarea></label>
            @if ($errors->any())
                <p class="mt-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</p>
            @endif
            <button class="eco-button eco-button-primary mt-4" type="submit">
                <i data-lucide="send"></i>
                <span>Send message</span>
            </button>
        </form>
    </section>
@endsection
