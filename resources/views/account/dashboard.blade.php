@extends('layouts.app')

@section('title', 'Dashboard')

@section('page_data')
    <script>
        window.ecoInitial = {
            dashboard: @json($dashboard),
        };
    </script>
@endsection

@section('content')
    <section class="dashboard-hero home-reveal">
        <div>
            <span class="eyebrow">{{ $user->roleLabel() }} workspace</span>
            <h1>Hi, {{ explode(' ', $user->name)[0] }}.</h1>
            <p>Submit a device, plan pickup, or find a center.</p>
            <div class="hero-actions">
                <a class="eco-button eco-button-primary" href="#deviceForm"><i data-lucide="scan-line"></i><span>Scan Device</span></a>
                <a class="eco-button eco-button-secondary" href="{{ route('pickup') }}"><i data-lucide="truck"></i><span>Schedule Pickup</span></a>
            </div>
        </div>
        <aside class="workspace-card">
            <span class="metric-label">Reward points</span>
            <strong data-dashboard="user.points">0</strong>
            <p>{{ $user->email_verified_at ? 'Verified profile' : 'Email verification pending' }}</p>
            @unless ($user->email_verified_at)
                <a class="eco-button eco-button-soft mt-4" href="{{ route('verification.otp') }}">
                    <i data-lucide="mail-check"></i>
                    <span>Verify email</span>
                </a>
            @endunless
        </aside>
    </section>

    <section class="quick-actions home-reveal delay-1">
        <a class="quick-card" href="#deviceForm"><i data-lucide="scan-line"></i><strong>Scan Device</strong><span>Recycle options</span></a>
        <a class="quick-card" href="{{ route('facilities') }}"><i data-lucide="map-pinned"></i><strong>Find Centers</strong><span>Nearby locations</span></a>
        <a class="quick-card" href="{{ route('pickup') }}"><i data-lucide="calendar-check"></i><strong>Pickup</strong><span>Book collection</span></a>
        <a class="quick-card" href="{{ route('rewards') }}"><i data-lucide="gift"></i><strong>Rewards</strong><span>Points wallet</span></a>
    </section>

    <section class="dashboard-metrics home-reveal delay-2">
        <article class="metric-panel"><span>Recycled</span><strong><span data-dashboard="user.devices">0</span></strong><p>Devices</p></article>
        <article class="metric-panel"><span>Centers</span><strong>{{ $dashboard['operations']['facilities'] ?? 12 }}</strong><p>Nearby</p></article>
        <article class="metric-panel"><span>Rewards</span><strong data-dashboard="user.points">0</strong><p>Points</p></article>
        <article class="metric-panel"><span>Requests</span><strong>{{ $requests->count() }}</strong><p>Recent</p></article>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Submit device</span><h2>Request recycling</h2></div>
                <span class="icon-badge"><i data-lucide="package-plus"></i></span>
            </div>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="POST" action="{{ route('customer.requests.store') }}">
                @csrf
                <label class="field"><span>Category</span><input name="category" placeholder="Mobile, laptop, TV" required></label>
                <label class="field"><span>Brand</span><input name="brand" placeholder="Apple, Dell, Samsung"></label>
                <label class="field"><span>Device model</span><input name="model" placeholder="iPhone 12, Inspiron 15" required></label>
                <label class="field"><span>Condition</span>
                    <select name="condition" required>
                        <option value="working">Working</option>
                        <option value="minor repair">Minor repair</option>
                        <option value="not working">Not working</option>
                        <option value="battery risk">Battery risk</option>
                        <option value="unknown">Unknown</option>
                    </select>
                </label>
                <label class="field"><span>Center</span>
                    <select name="recycling_center_id">
                        <option value="">Auto assign active center</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }} / {{ $center->city }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Preferred slot</span><input name="preferred_slot" placeholder="Tomorrow, 10 AM - 1 PM"></label>
                <label class="field md:col-span-2"><span>Pickup address</span><input name="pickup_address" placeholder="Apartment, street, city"></label>
                <button class="eco-button eco-button-primary md:col-span-2" type="submit"><i data-lucide="send"></i><span>Submit Device</span></button>
            </form>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">History</span><h2>Recent submissions</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($requests as $requestRow)
                    <div class="activity-row">
                        <div>
                            <strong class="text-sm">{{ $requestRow->device?->model ?? 'Device' }}</strong>
                            <p class="mt-1 text-xs">{{ $requestRow->request_number }} - {{ $requestRow->recyclingCenter?->name ?? 'Center pending' }}</p>
                        </div>
                        <span class="status-chip {{ in_array($requestRow->status, ['approved', 'completed'], true) ? 'open' : '' }}">{{ $requestRow->status }}</span>
                    </div>
                @empty
                    <div class="empty-state">No submissions yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="section-band">
        <div class="section-head">
            <div><span class="eyebrow">Smart scan</span><h2>Check a device</h2></div>
            <span class="icon-badge"><i data-lucide="scan-line"></i></span>
        </div>
        <form id="deviceForm" class="mt-4 grid gap-4 md:grid-cols-[1fr_auto]" enctype="multipart/form-data">
            <label class="field">
                <span>Device</span>
                <input name="model_name" type="text" placeholder="iPhone, Dell laptop, LED TV" required>
            </label>
            <label class="field">
                <span>Condition</span>
                <select name="condition">
                    <option value="unknown">Unknown</option>
                    <option value="working">Working</option>
                    <option value="minor repair">Minor repair</option>
                    <option value="not working">Not working</option>
                    <option value="battery risk">Battery risk</option>
                </select>
            </label>
            <button class="eco-button eco-button-primary md:self-end" type="submit">
                <i data-lucide="sparkles"></i>
                <span>Analyze</span>
            </button>
        </form>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1fr_0.9fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Nearby centers</span><h2>Recommended</h2></div>
                <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}"><i data-lucide="arrow-right"></i><span>View all</span></a>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($centers->take(3) as $center)
                    <article class="facility-card">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <h3>{{ $center->name }}</h3>
                                <span class="status-chip {{ $center->status === 'active' ? 'open' : 'closed' }}">{{ ucfirst($center->status) }}</span>
                            </div>
                            <p>{{ $center->city }}{{ $center->state ? ' / '.$center->state : '' }}</p>
                        </div>
                        <a class="eco-button eco-button-secondary" href="{{ route('facilities') }}"><span>View</span></a>
                    </article>
                @empty
                    <div class="empty-state">No active centers yet.</div>
                @endforelse
            </div>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Pickup status</span><h2>Timeline</h2></div>
            </div>
            <div class="timeline mt-5">
                <div class="timeline-step"><span>1</span><strong>Requested</strong></div>
                <div class="timeline-step"><span>2</span><strong>Assigned</strong></div>
                <div class="timeline-step"><span>3</span><strong>Picked Up</strong></div>
                <div class="timeline-step"><span>4</span><strong>Recycled</strong></div>
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Rewards</span><h2>Your progress</h2></div></div>
            <div class="mt-5">
                <div class="flex items-end justify-between gap-3">
                    <strong class="text-4xl font-black" data-dashboard="user.points">0</strong>
                    <span class="reward-pill">Eco Starter</span>
                </div>
                <div class="reward-progress mt-4"><span style="width: 64%"></span></div>
            </div>
            <div id="badges" class="mt-4 grid gap-2"></div>
            <div class="mt-4 grid gap-2">
                @forelse ($rewardPoints as $reward)
                    <div class="coupon-row"><span class="text-sm font-bold">{{ $reward->description }}</span><span class="status-chip open">+{{ $reward->points }} pts</span></div>
                @empty
                    <div class="empty-state">Approved recycling rewards will appear here.</div>
                @endforelse
            </div>
        </article>

        <article id="notifications" class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Updates</span><h2>Notifications</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($notifications as $notification)
                    <div class="notification-row {{ $notification->read_at ? '' : 'unread' }}">
                        <div>
                            <strong>{{ $notification->title }}</strong>
                            <p>{{ $notification->body }}</p>
                        </div>
                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <button class="icon-button" type="submit" aria-label="Mark read"><i data-lucide="check"></i></button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <div class="empty-state">No notifications yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    @include('sustainability.partials.analysis-modal')
@endsection
