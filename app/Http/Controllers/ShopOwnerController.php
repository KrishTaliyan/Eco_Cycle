<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\RecyclingCenter;
use App\Models\RecyclingRequest;
use App\Models\RewardPoint;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopOwnerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');
        $centerIds = $user->ownedRecyclingCenters()->pluck('id');
        $requestScope = function ($query) use ($user, $centerIds, $isAdmin) {
            return $query->when(! $isAdmin, function ($query) use ($user, $centerIds) {
                $query->where(function ($query) use ($user, $centerIds) {
                    $query->where('shop_owner_id', $user->id)
                        ->orWhereIn('recycling_center_id', $centerIds);
                });
            });
        };

        $requests = $requestScope(RecyclingRequest::with(['customer', 'device', 'recyclingCenter']))
            ->latest()
            ->paginate(8);

        return view('shop.dashboard', [
            'user' => $user,
            'isAdminView' => $isAdmin,
            'shopOwners' => User::where('role', 'shop_owner')->orderBy('name')->get(['id', 'name', 'email']),
            'centers' => $isAdmin
                ? RecyclingCenter::with('shopOwner')->latest()->get()
                : $user->ownedRecyclingCenters()->latest()->get(),
            'requests' => $requests,
            'stats' => [
                'total' => $requestScope(RecyclingRequest::query())->count(),
                'accepted' => $requestScope(RecyclingRequest::query())->whereIn('status', ['approved', 'collected', 'completed'])->count(),
                'pending' => $requestScope(RecyclingRequest::query())->where('status', 'pending')->count(),
                'points' => $requestScope(RecyclingRequest::query())->sum('reward_points'),
                'devices' => $isAdmin ? Device::count() : Device::whereIn('recycling_center_id', $centerIds)->count(),
            ],
        ]);
    }

    public function storeCenter(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'accepted_categories' => ['nullable', 'string', 'max:255'],
            'shop_owner_id' => $request->user()->hasRole('admin')
                ? ['required', Rule::exists('users', 'id')->where('role', 'shop_owner')]
                : ['nullable'],
        ]);

        $validated['shop_owner_id'] = $request->user()->hasRole('admin')
            ? $validated['shop_owner_id']
            : $request->user()->id;
        $validated['accepted_categories'] = collect(explode(',', $validated['accepted_categories'] ?? 'Mobile,Laptop,Battery,TV'))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        RecyclingCenter::create($validated);

        return back()->with('status', 'Recycling center added.');
    }

    public function updateRequest(Request $request, RecyclingRequest $recyclingRequest, NotificationService $notifications)
    {
        abort_unless(
            $request->user()->hasRole('admin')
            || $recyclingRequest->shop_owner_id === $request->user()->id
            || $request->user()->ownedRecyclingCenters()->whereKey($recyclingRequest->recycling_center_id)->exists(),
            403
        );

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'collected', 'completed'])],
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
                'description' => 'Reward for '.$recyclingRequest->device?->model,
            ]);

            $notifications->send(
                user: $customer,
                title: 'Request '.$validated['status'],
                body: $validated['reward_points'].' reward points assigned.',
                type: 'reward',
                actionLabel: 'View rewards',
                actionUrl: route('dashboard').'#notifications',
                metadata: ['request_id' => $recyclingRequest->id],
            );
        }

        return back()->with('status', 'Request updated.');
    }
}
