<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\PickupRequest;
use App\Models\RecyclingActivity;
use App\Models\RecyclingCenter;
use App\Models\RecyclingCertificate;
use App\Models\RecyclingRequest;
use App\Models\RewardPoint;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        return view('admin.dashboard', [
            'dashboard' => $dashboard->snapshot($request->session()->getId(), $request->user()),
            'users' => User::with('roles')->latest()->take(10)->get(),
            'shopOwners' => User::where('role', 'shop_owner')->orderBy('name')->get(['id', 'name', 'email']),
            'logs' => ActivityLog::query()->latest('created_at')->take(10)->get(),
            'pickups' => PickupRequest::query()->latest()->take(8)->get(),
            'centers' => RecyclingCenter::with('shopOwner')->latest()->take(8)->get(),
            'requests' => RecyclingRequest::with(['customer', 'shopOwner', 'device', 'recyclingCenter'])->latest()->take(10)->get(),
            'roleOptions' => User::roleOptions(),
            'roleCounts' => User::query()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
            'queues' => [
                'pending_requests' => RecyclingRequest::where('status', 'pending')->count(),
                'unassigned_centers' => RecyclingCenter::whereNull('shop_owner_id')->count(),
                'inactive_centers' => RecyclingCenter::where('status', 'inactive')->count(),
                'open_pickups' => PickupRequest::whereNotIn('status', ['completed', 'cancelled'])->count(),
                'recent_actions' => ActivityLog::where('created_at', '>=', now()->subDay())->count(),
            ],
            'totals' => [
                'users' => User::count(),
                'customers' => User::where('role', 'customer')->count(),
                'shop_owners' => User::where('role', 'shop_owner')->count(),
                'devices' => Device::count(),
                'reward_points' => RewardPoint::sum('points'),
                'centers' => RecyclingCenter::where('status', 'active')->count(),
                'activities' => RecyclingActivity::count(),
                'certificates' => RecyclingCertificate::count(),
                'pickups' => PickupRequest::count(),
            ],
            'charts' => [
                'categories' => Device::query()
                    ->selectRaw('category, count(*) as total')
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->take(5)
                    ->pluck('total', 'category'),
                'growth' => User::query()
                    ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
                    ->get()
                    ->groupBy(fn (User $user) => $user->created_at->format('M'))
                    ->map->count(),
            ],
        ]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'customer', 'shop_owner'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($validated);
        $user->assignRole($validated['role']);
        $user->settings()->firstOrCreate([]);

        return back()->with('status', 'User created.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'customer', 'shop_owner'])],
        ]);

        $user->assignRole($validated['role']);

        return back()->with('status', 'User role updated.');
    }

    public function destroyUser(User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'You cannot delete your own account.');

        $user->delete();

        return back()->with('status', 'User removed.');
    }

    public function storeCenter(Request $request)
    {
        $validated = $request->validate([
            'shop_owner_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'shop_owner')],
            'name' => ['required', 'string', 'max:140'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        RecyclingCenter::create($validated + [
            'accepted_categories' => ['Mobile', 'Laptop', 'Battery', 'TV'],
            'opening_hours' => '10:00 AM - 7:00 PM',
        ]);

        return back()->with('status', 'Center created.');
    }

    public function updateRequest(Request $request, RecyclingRequest $recyclingRequest, NotificationService $notifications)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'collected', 'completed'])],
            'reward_points' => ['required', 'integer', 'min:0', 'max:5000'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $recyclingRequest->fill($validated);
        $recyclingRequest->approved_at = in_array($validated['status'], ['approved', 'collected', 'completed'], true) ? now() : null;
        $recyclingRequest->rejected_at = $validated['status'] === 'rejected' ? now() : null;
        $recyclingRequest->save();
        $recyclingRequest->device?->update(['status' => $validated['status']]);

        $customer = $recyclingRequest->customer;

        if (in_array($validated['status'], ['approved', 'completed'], true) && $customer) {
            RewardPoint::updateOrCreate([
                'user_id' => $customer->id,
                'recycling_request_id' => $recyclingRequest->id,
            ], [
                'points' => $validated['reward_points'],
                'type' => 'earned',
                'description' => 'Admin reward approval',
            ]);
        }

        if ($customer) {
            $notifications->send(
                user: $customer,
                title: 'Request '.$validated['status'],
                body: 'Status updated for '.$recyclingRequest->device?->model.'.',
                type: 'request',
                actionLabel: 'View dashboard',
                actionUrl: route('dashboard').'#notifications',
                metadata: ['request_id' => $recyclingRequest->id],
            );
        }

        return back()->with('status', 'Request status updated.');
    }
}
