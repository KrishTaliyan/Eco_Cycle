<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Services\ActivityLogger;
use App\Services\DeviceIntelligenceService;
use App\Services\FacilityFinder;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function schedule(
        Request $request,
        DeviceIntelligenceService $devices,
        FacilityFinder $facilities,
        ActivityLogger $logger,
        NotificationService $notifications,
    ) {
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
        $bookingId = 'IN-PU-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        $pickup = PickupRequest::create([
            'user_id' => $request->user()?->id,
            'session_id' => $sessionId,
            'booking_id' => $bookingId,
            'device_model' => $analysis['identified_model'],
            'city' => $validated['city'],
            'pincode' => $validated['pincode'] ?? null,
            'preferred_window' => $validated['preferred_window'],
            'facility' => $nearest,
            'prep_checklist' => $analysis['prep_checklist'],
            'points_preview' => $analysis['points'] + 40,
        ]);

        if ($request->user()) {
            $notifications->send(
                $request->user(),
                'Pickup request prepared',
                "{$pickup->booking_id} is matched to ".($nearest['name'] ?? 'a nearby partner').'.',
                'pickup',
                'View dashboard',
                route('dashboard'),
                ['booking_id' => $pickup->booking_id],
            );
        }

        $logger->record('pickup.scheduled', 'Prepared an e-waste pickup request.', $request, $request->user(), [
            'booking_id' => $pickup->booking_id,
            'city' => $pickup->city,
            'device' => $pickup->device_model,
        ]);

        return response()->json([
            'booking_id' => $pickup->booking_id,
            'status' => $pickup->status,
            'city' => $validated['city'],
            'pincode' => $validated['pincode'] ?? null,
            'preferred_window' => $validated['preferred_window'],
            'facility' => $nearest,
            'points_preview' => $pickup->points_preview,
            'message' => 'Pickup request prepared. A production app can send this to an email/SMS/CRM provider.',
            'prep_checklist' => $analysis['prep_checklist'],
        ], 201);
    }
}
