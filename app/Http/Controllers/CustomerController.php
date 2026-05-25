<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\RecyclingCenter;
use App\Models\RecyclingRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function storeRequest(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:120'],
            'condition' => ['required', Rule::in(['working', 'minor repair', 'not working', 'battery risk', 'unknown'])],
            'recycling_center_id' => ['nullable', 'exists:recycling_centers,id'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'preferred_slot' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $center = isset($validated['recycling_center_id'])
            ? RecyclingCenter::find($validated['recycling_center_id'])
            : RecyclingCenter::query()->where('status', 'active')->first();

        $points = $this->pointsFor($validated['category'], $validated['condition']);

        $device = Device::create([
            'user_id' => $request->user()->id,
            'recycling_center_id' => $center?->id,
            'category' => $validated['category'],
            'brand' => $validated['brand'] ?? null,
            'model' => $validated['model'],
            'condition' => $validated['condition'],
            'estimated_weight_kg' => $this->weightFor($validated['category']),
            'points_preview' => $points,
            'status' => 'submitted',
            'notes' => $validated['notes'] ?? null,
        ]);

        $recyclingRequest = RecyclingRequest::create([
            'customer_id' => $request->user()->id,
            'shop_owner_id' => $center?->shop_owner_id,
            'recycling_center_id' => $center?->id,
            'device_id' => $device->id,
            'request_number' => 'EW-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'pickup_address' => $validated['pickup_address'] ?? null,
            'preferred_slot' => $validated['preferred_slot'] ?? null,
            'reward_points' => $points,
            'status' => 'pending',
        ]);

        $notifications->send(
            user: $request->user(),
            title: 'Device submitted',
            body: 'Your request is waiting for review.',
            type: 'request',
            actionLabel: 'View request',
            actionUrl: route('dashboard').'#notifications',
            metadata: ['request_id' => $recyclingRequest->id],
        );

        if ($center?->shopOwner) {
            $notifications->send(
                user: $center->shopOwner,
                title: 'New recycling request',
                body: ($request->user()->name).' submitted '.$validated['model'].'.',
                type: 'request',
                actionLabel: 'Review',
                actionUrl: route('shop.dashboard'),
                metadata: ['request_id' => $recyclingRequest->id],
            );
        }

        return back()->with('status', 'Device submitted. Reward points will be added after approval.');
    }

    private function pointsFor(string $category, string $condition): int
    {
        $base = match (Str::lower($category)) {
            'laptop', 'computer' => 180,
            'mobile', 'phone', 'smartphone' => 120,
            'tv', 'television', 'monitor' => 220,
            'battery' => 90,
            default => 75,
        };

        return $condition === 'working' ? $base + 40 : $base;
    }

    private function weightFor(string $category): float
    {
        return match (Str::lower($category)) {
            'laptop', 'computer' => 2.4,
            'mobile', 'phone', 'smartphone' => 0.2,
            'tv', 'television', 'monitor' => 8.5,
            'battery' => 0.5,
            default => 1.2,
        };
    }
}
