@extends('layouts.app')

@section('title', 'Shop Owner Dashboard')

@section('content')
    <section class="dashboard-hero">
        <div>
            <span class="eyebrow">{{ $isAdminView ? 'Admin shop view' : 'Shop owner' }}</span>
            <h1>Center operations.</h1>
            <p>Centers, requests, rewards, and collection status.</p>
        </div>
        <aside class="workspace-card">
            <span class="metric-label">{{ $isAdminView ? 'Admin access' : 'Operator' }}</span>
            <strong>{{ $user->name }}</strong>
            <p>{{ $centers->count() }} {{ \Illuminate\Support\Str::plural('center', $centers->count()) }}</p>
        </aside>
    </section>

    <section class="dashboard-metrics">
        <article class="metric-panel"><span>Requests</span><strong>{{ $stats['total'] }}</strong><p>Total</p></article>
        <article class="metric-panel"><span>Accepted</span><strong>{{ $stats['accepted'] }}</strong><p>Approved+</p></article>
        <article class="metric-panel"><span>Pending</span><strong>{{ $stats['pending'] }}</strong><p>Review</p></article>
        <article class="metric-panel"><span>Rewards</span><strong>{{ $stats['points'] }}</strong><p>Points</p></article>
        <article class="metric-panel"><span>Devices</span><strong>{{ $stats['devices'] }}</strong><p>Records</p></article>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Center profile</span><h2>Add center</h2></div>
                <span class="icon-badge"><i data-lucide="store"></i></span>
            </div>
            <form class="mt-4 grid gap-3" method="POST" action="{{ route('shop.centers.store') }}">
                @csrf
                @if ($isAdminView)
                    <label class="field"><span>Owner</span>
                        <select name="shop_owner_id" required>
                            <option value="">Choose shop owner</option>
                            @foreach ($shopOwners as $owner)
                                <option value="{{ $owner->id }}">{{ $owner->name }} / {{ $owner->email }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="field"><span>Name</span><input name="name" required placeholder="Green Loop Center"></label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="field"><span>City</span><input name="city" required placeholder="Delhi NCR"></label>
                    <label class="field"><span>State</span><input name="state" placeholder="Delhi"></label>
                </div>
                <label class="field"><span>Address</span><input name="address" required placeholder="Full collection center address"></label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="field"><span>Phone</span><input name="phone" placeholder="+91 ..."></label>
                    <label class="field"><span>Email</span><input name="email" type="email" placeholder="center@example.com"></label>
                </div>
                <label class="field"><span>Opening hours</span><input name="opening_hours" placeholder="10:00 AM - 7:00 PM"></label>
                <label class="field"><span>Accepted categories</span><input name="accepted_categories" placeholder="Mobile, Laptop, Battery, TV"></label>
                <button class="eco-button eco-button-primary" type="submit"><i data-lucide="plus"></i><span>Add center</span></button>
            </form>

            <div class="mt-5 grid gap-3">
                @forelse ($centers as $center)
                    <div class="facility-card">
                        <div>
                            <h3>{{ $center->name }}</h3>
                            <p>{{ $center->address }}</p>
                            @if ($isAdminView)
                                <p>{{ $center->shopOwner?->name ?? 'Unassigned' }}</p>
                            @endif
                            <div class="facility-services">
                                @foreach (($center->accepted_categories ?? []) as $category)
                                    <span class="facility-tag">{{ $category }}</span>
                                @endforeach
                            </div>
                        </div>
                        <span class="status-chip {{ $center->status === 'active' ? 'open' : 'closed' }}">{{ $center->status }}</span>
                    </div>
                @empty
                    <div class="empty-state">Add your first center to start receiving submissions.</div>
                @endforelse
            </div>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Requests</span><h2>Review queue</h2></div>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($requests as $requestRow)
                    <article class="activity-row items-start">
                        <div>
                            <strong class="text-sm">{{ $requestRow->device?->model ?? 'Device' }}</strong>
                            <p class="mt-1 text-xs">{{ $requestRow->customer?->name ?? 'Customer' }} / {{ $requestRow->recyclingCenter?->name ?? 'Center pending' }}</p>
                            <p class="mt-1 text-xs">{{ $requestRow->request_number }}</p>
                            <span class="status-chip mt-2">{{ $requestRow->status }}</span>
                        </div>
                        <form class="flex flex-wrap justify-end gap-2" method="POST" action="{{ route('shop.requests.update', $requestRow) }}">
                            @csrf
                            @method('PUT')
                            <select name="status" class="min-h-10 rounded-lg border px-3 text-sm" style="border-color:var(--app-border);background:var(--app-card);">
                                @foreach (['approved', 'rejected', 'collected', 'completed'] as $status)
                                    <option value="{{ $status }}" @selected($requestRow->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <input name="reward_points" type="number" min="0" value="{{ $requestRow->reward_points }}" class="min-h-10 w-24 rounded-lg border px-3 text-sm" style="border-color:var(--app-border);background:var(--app-card);">
                            <button class="eco-button eco-button-primary" type="submit"><i data-lucide="check"></i><span>Save</span></button>
                        </form>
                    </article>
                @empty
                    <div class="empty-state">No submissions yet.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        </article>
    </section>
@endsection
