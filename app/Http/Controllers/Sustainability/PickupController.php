<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Services\DeviceIntelligenceService;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function schedule(Request $request, DeviceIntelligenceService $devices, FacilityFinder $facilities)
    {
        $validated = $request->validate([
            'model_name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'pincode' => ['nullable', 'digits:6'],
            'preferred_window' => ['required', 'string', 'max:60'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $city = collect($facilities->cityPresets())
            ->first(fn (array $preset) => Str::lower($preset['city']) === Str::lower($validated['city']));

        $lat = (float) ($validated['lat'] ?? $city['lat'] ?? 28.6139);
        $lng = (float) ($validated['lng'] ?? $city['lng'] ?? 77.2090);
        $nearest = $facilities->nearest($lat, $lng, 1)[0] ?? null;
        $analysis = $devices->analyze($validated['model_name']);

        return response()->json([
            'booking_id' => 'IN-PU-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'status' => 'pickup-window-held',
            'city' => $validated['city'],
            'pincode' => $validated['pincode'] ?? null,
            'preferred_window' => $validated['preferred_window'],
            'facility' => $nearest,
            'points_preview' => $analysis['points'] + 40,
            'message' => 'Pickup request prepared. A production app can send this to an email/SMS/CRM provider.',
            'prep_checklist' => $analysis['prep_checklist'],
        ], 201);
    }
}
