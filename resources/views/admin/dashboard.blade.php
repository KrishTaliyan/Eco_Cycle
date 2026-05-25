@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('page_data')
    <script>window.ecoInitial = { dashboard: @json($dashboard) };</script>
@endsection

@section('content')
    <section class="dashboard-hero">
        <div>
            <span class="eyebrow">Admin ops</span>
            <h1>Platform control room.</h1>
            <p>Users, centers, requests, rewards, and audit activity in one place.</p>
            <div class="hero-actions">
                <a class="eco-button eco-button-primary" href="#requests"><i data-lucide="clipboard-check"></i><span>Requests</span></a>
                <a class="eco-button eco-button-secondary" href="#users"><i data-lucide="users"></i><span>Users</span></a>
            </div>
        </div>
        <aside class="workspace-card">
            <span class="metric-label">Administrator</span>
            <strong>{{ auth()->user()->name }}</strong>
            <p>{{ auth()->user()->roleDescription() }}</p>
        </aside>
    </section>

    <section class="dashboard-metrics">
        <article class="metric-panel"><span>Users</span><strong>{{ $totals['users'] }}</strong><p>Accounts</p></article>
        <article class="metric-panel"><span>Customers</span><strong>{{ $totals['customers'] }}</strong><p>Recyclers</p></article>
        <article class="metric-panel"><span>Shop Owners</span><strong>{{ $totals['shop_owners'] }}</strong><p>Operators</p></article>
        <article class="metric-panel"><span>Devices</span><strong>{{ $totals['devices'] }}</strong><p>Submitted</p></article>
        <article class="metric-panel"><span>Rewards</span><strong>{{ $totals['reward_points'] }}</strong><p>Points</p></article>
        <article class="metric-panel"><span>Centers</span><strong>{{ $totals['centers'] }}</strong><p>Active</p></article>
        <article class="metric-panel"><span>Pickups</span><strong>{{ $totals['pickups'] }}</strong><p>Scheduled</p></article>
        <article class="metric-panel"><span>Certificates</span><strong>{{ $totals['certificates'] }}</strong><p>Issued</p></article>
    </section>

    <section class="role-grid mt-5">
        @foreach ($roleOptions as $role => $meta)
            <article class="role-card">
                <span class="icon-badge"><i data-lucide="{{ $meta['icon'] }}"></i></span>
                <div>
                    <strong>{{ $meta['label'] }}</strong>
                    <p>{{ $meta['description'] }}</p>
                </div>
                <span class="status-chip">{{ $roleCounts[$role] ?? 0 }}</span>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Monthly recycling</span><h2>Activity</h2></div></div>
            <div id="monthlyGraph" class="monthly-graph mt-4"></div>
        </article>
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Devices</span><h2>Submission mix</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($charts['categories'] as $category => $total)
                    <div class="activity-row">
                        <strong class="text-sm">{{ $category }}</strong>
                        <span class="status-chip open">{{ $total }}</span>
                    </div>
                @empty
                    <div class="empty-state">No device category data yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section id="requests" class="mt-5 surface p-4 sm:p-5">
        <div class="section-head">
            <div><span class="eyebrow">Requests</span><h2>Review submissions</h2></div>
        </div>
        <div class="mt-4 grid gap-3">
            @forelse ($requests as $requestRow)
                <article class="activity-row items-start">
                    <div>
                        <strong class="text-sm">{{ $requestRow->request_number }}</strong>
                        <p class="mt-1 text-xs">{{ $requestRow->customer?->name ?? 'Guest' }} / {{ $requestRow->device?->model ?? 'Device' }} / {{ $requestRow->recyclingCenter?->name ?? 'No center' }}</p>
                    </div>
                    <form class="flex flex-wrap items-center justify-end gap-2" method="POST" action="{{ route('admin.requests.update', $requestRow) }}">
                        @csrf
                        @method('PUT')
                        <select name="status" class="min-h-10 rounded-lg border px-3 text-sm" style="border-color:var(--app-border);background:var(--app-card);">
                            @foreach (['pending', 'approved', 'rejected', 'collected', 'completed'] as $status)
                                <option value="{{ $status }}" @selected($requestRow->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <input name="reward_points" type="number" value="{{ $requestRow->reward_points }}" min="0" class="min-h-10 w-24 rounded-lg border px-3 text-sm" style="border-color:var(--app-border);background:var(--app-card);">
                        <button class="eco-button eco-button-primary" type="submit"><i data-lucide="save"></i><span>Save</span></button>
                    </form>
                </article>
            @empty
                <div class="empty-state">No recycling submissions yet.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-2">
        <article id="users" class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Users</span><h2>Roles and access</h2></div></div>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <label class="field"><span>Name</span><input name="name" required></label>
                <label class="field"><span>Email</span><input name="email" type="email" required></label>
                <label class="field"><span>Role</span>
                    <select name="role">
                        @foreach ($roleOptions as $role => $meta)
                            <option value="{{ $role }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Password</span><input name="password" type="password" required></label>
                <button class="eco-button eco-button-secondary md:col-span-2" type="submit"><i data-lucide="user-plus"></i><span>Create user</span></button>
            </form>
            <div class="mt-4 grid gap-3">
                @foreach ($users as $row)
                    <div class="activity-row items-start">
                        <div>
                            <strong class="text-sm">{{ $row->name }}</strong>
                            <p class="mt-1 text-xs">{{ $row->email }}</p>
                            <span class="status-chip mt-2">{{ $row->roleLabel() }}</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <form class="flex items-center gap-2" method="POST" action="{{ route('admin.users.update', $row) }}">
                                @csrf
                                @method('PUT')
                                <select name="role" class="min-h-10 rounded-lg border px-2 text-sm" style="border-color:var(--app-border);background:var(--app-card);">
                                    @foreach ($roleOptions as $role => $meta)
                                        <option value="{{ $role }}" @selected($row->role === $role)>{{ $meta['label'] }}</option>
                                    @endforeach
                                </select>
                                <button class="icon-button" type="submit" aria-label="Update role"><i data-lucide="save"></i></button>
                            </form>
                            @unless (auth()->id() === $row->id)
                                <form method="POST" action="{{ route('admin.users.destroy', $row) }}" onsubmit="return confirm('Remove this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-button" type="submit" aria-label="Remove user"><i data-lucide="x"></i></button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Centers</span><h2>Recycling network</h2></div></div>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="POST" action="{{ route('admin.centers.store') }}">
                @csrf
                <label class="field"><span>Name</span><input name="name" required></label>
                <label class="field"><span>Owner</span>
                    <select name="shop_owner_id">
                        <option value="">Unassigned</option>
                        @foreach ($shopOwners as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>City</span><input name="city" required></label>
                <label class="field"><span>State</span><input name="state"></label>
                <label class="field"><span>Phone</span><input name="phone"></label>
                <label class="field"><span>Status</span>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label class="field md:col-span-2"><span>Address</span><input name="address" required></label>
                <button class="eco-button eco-button-secondary" type="submit"><i data-lucide="plus"></i><span>Add Center</span></button>
            </form>
            <div class="mt-4 grid gap-3">
                @forelse ($centers as $center)
                    <div class="activity-row">
                        <div>
                            <strong class="text-sm">{{ $center->name }}</strong>
                            <p class="mt-1 text-xs">{{ $center->city }} / {{ $center->shopOwner?->name ?? 'Unassigned' }}</p>
                        </div>
                        <span class="status-chip {{ $center->status === 'active' ? 'open' : 'closed' }}">{{ $center->status }}</span>
                    </div>
                @empty
                    <div class="empty-state">No centers created.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-2">
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Activity</span><h2>Audit trail</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($logs as $log)
                    <div class="log-row"><span>{{ $log->created_at?->diffForHumans() }}</span><strong>{{ $log->event }}</strong><p>{{ $log->description }}</p></div>
                @empty
                    <div class="empty-state">No events yet.</div>
                @endforelse
            </div>
        </article>
        <article class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Pickups</span><h2>Scheduled</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($pickups as $pickup)
                    <div class="activity-row"><div><strong class="text-sm">{{ $pickup->booking_id }}</strong><p class="mt-1 text-xs">{{ $pickup->device_model }} - {{ $pickup->city }}</p></div><span class="status-chip open">{{ $pickup->status }}</span></div>
                @empty
                    <div class="empty-state">No pickup requests yet.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
