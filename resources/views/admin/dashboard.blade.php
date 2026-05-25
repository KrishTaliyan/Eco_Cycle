@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('page_data')
    <script>window.ecoInitial = { dashboard: @json($dashboard) };</script>
@endsection

@section('content')
    <section class="admin-hero">
        <div>
            <span class="eyebrow">Admin console</span>
            <h1>Control platform access and approvals.</h1>
            <div class="hero-actions">
                <a class="eco-button eco-button-primary" href="#requests"><i data-lucide="clipboard-check"></i><span>Review</span></a>
                <a class="eco-button eco-button-secondary" href="#users"><i data-lucide="users"></i><span>Users</span></a>
                <a class="eco-button eco-button-secondary" href="#audit"><i data-lucide="shield-check"></i><span>Audit</span></a>
            </div>
        </div>
        <aside class="admin-hero-panel">
            <span class="metric-label">Responsible admin</span>
            <strong>{{ auth()->user()->name }}</strong>
            <div class="admin-hero-stack">
                <span><i data-lucide="shield-check"></i> Access</span>
                <span><i data-lucide="clipboard-check"></i> Approvals</span>
                <span><i data-lucide="building2"></i> Centers</span>
            </div>
        </aside>
    </section>

    <section class="admin-duty-grid mt-5">
        <a class="admin-duty-card" href="#requests">
            <span class="icon-badge"><i data-lucide="clipboard-check"></i></span>
            <div><strong>Pending review</strong><p>{{ $queues['pending_requests'] ?? 0 }} requests</p></div>
            <i data-lucide="chevron-right"></i>
        </a>
        <a class="admin-duty-card" href="#centers">
            <span class="icon-badge"><i data-lucide="building2"></i></span>
            <div><strong>Unassigned centers</strong><p>{{ $queues['unassigned_centers'] ?? 0 }} need owner</p></div>
            <i data-lucide="chevron-right"></i>
        </a>
        <a class="admin-duty-card" href="#centers">
            <span class="icon-badge"><i data-lucide="triangle-alert"></i></span>
            <div><strong>Inactive centers</strong><p>{{ $queues['inactive_centers'] ?? 0 }} offline</p></div>
            <i data-lucide="chevron-right"></i>
        </a>
        <a class="admin-duty-card" href="#audit">
            <span class="icon-badge"><i data-lucide="shield-check"></i></span>
            <div><strong>Recent actions</strong><p>{{ $queues['recent_actions'] ?? 0 }} today</p></div>
            <i data-lucide="chevron-right"></i>
        </a>
    </section>

    <section class="dashboard-metrics admin-metrics">
        <article class="metric-panel"><span>Users</span><strong>{{ $totals['users'] }}</strong><p>Accounts</p></article>
        <article class="metric-panel"><span>Customers</span><strong>{{ $totals['customers'] }}</strong><p>Recyclers</p></article>
        <article class="metric-panel"><span>Owners</span><strong>{{ $totals['shop_owners'] }}</strong><p>Shops</p></article>
        <article class="metric-panel"><span>Devices</span><strong>{{ $totals['devices'] }}</strong><p>Submitted</p></article>
        <article class="metric-panel"><span>Rewards</span><strong>{{ number_format($totals['reward_points']) }}</strong><p>Points</p></article>
        <article class="metric-panel"><span>Centers</span><strong>{{ $totals['centers'] }}</strong><p>Active</p></article>
        <article class="metric-panel"><span>Pickups</span><strong>{{ $totals['pickups'] }}</strong><p>Booked</p></article>
        <article class="metric-panel"><span>Certificates</span><strong>{{ $totals['certificates'] }}</strong><p>Issued</p></article>
    </section>

    <section class="role-grid admin-role-grid mt-5">
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
            <div class="section-head"><div><span class="eyebrow">Activity</span><h2>Monthly recycling</h2></div></div>
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
                    <div class="empty-state">No category data yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section id="requests" class="mt-5 surface p-4 sm:p-5">
        <div class="section-head">
            <div><span class="eyebrow">Requests</span><h2>Approve submissions</h2></div>
            <span class="status-chip">{{ $requests->count() }} latest</span>
        </div>
        <div class="mt-4 grid gap-3">
            @forelse ($requests as $requestRow)
                @php($statusClass = in_array($requestRow->status, ['approved', 'collected', 'completed'], true) ? 'open' : ($requestRow->status === 'rejected' ? 'closed' : ''))
                <article class="activity-row admin-request-row items-start">
                    <div class="admin-request-main">
                        <strong class="text-sm">{{ $requestRow->request_number }}</strong>
                        <p class="mt-1 text-xs">{{ $requestRow->customer?->name ?? 'Guest' }} / {{ $requestRow->device?->model ?? 'Device' }} / {{ $requestRow->recyclingCenter?->name ?? 'No center' }}</p>
                        <span class="status-chip mt-2 {{ $statusClass }}">{{ ucfirst($requestRow->status) }}</span>
                    </div>
                    <form class="admin-request-form" method="POST" action="{{ route('admin.requests.update', $requestRow) }}">
                        @csrf
                        @method('PUT')
                        <select name="status" aria-label="Request status">
                            @foreach (['pending', 'approved', 'rejected', 'collected', 'completed'] as $status)
                                <option value="{{ $status }}" @selected($requestRow->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <input name="reward_points" type="number" value="{{ $requestRow->reward_points }}" min="0" aria-label="Reward points">
                        <input name="admin_note" type="text" value="{{ $requestRow->admin_note }}" maxlength="500" placeholder="Admin note" aria-label="Admin note">
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
            <div class="section-head">
                <div><span class="eyebrow">Users</span><h2>Roles and access</h2></div>
                <span class="status-chip">{{ $totals['users'] }} total</span>
            </div>
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
                                    <button class="icon-button danger" type="submit" aria-label="Remove user"><i data-lucide="x"></i></button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article id="centers" class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Centers</span><h2>Recycling network</h2></div>
                <span class="status-chip">{{ $totals['centers'] }} active</span>
            </div>
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
                <button class="eco-button eco-button-secondary" type="submit"><i data-lucide="plus"></i><span>Add center</span></button>
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
        <article id="audit" class="surface p-4 sm:p-5">
            <div class="section-head"><div><span class="eyebrow">Audit</span><h2>Activity trail</h2></div></div>
            <div class="mt-4 grid gap-3">
                @forelse ($logs as $log)
                    <div class="log-row"><span>{{ $log->created_at?->diffForHumans() }}</span><strong>{{ $log->event }}</strong><p>{{ $log->description }}</p></div>
                @empty
                    <div class="empty-state">No events yet.</div>
                @endforelse
            </div>
        </article>
        <article class="surface p-4 sm:p-5">
            <div class="section-head">
                <div><span class="eyebrow">Pickups</span><h2>Schedule queue</h2></div>
                <span class="status-chip">{{ $queues['open_pickups'] ?? 0 }} open</span>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($pickups as $pickup)
                    <div class="activity-row"><div><strong class="text-sm">{{ $pickup->booking_id }}</strong><p class="mt-1 text-xs">{{ $pickup->device_model }} / {{ $pickup->city }}</p></div><span class="status-chip open">{{ $pickup->status }}</span></div>
                @empty
                    <div class="empty-state">No pickup requests yet.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
