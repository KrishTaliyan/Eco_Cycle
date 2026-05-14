<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function nearest(Request $request, FacilityFinder $finder)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $facilities = $finder->nearest(
            (float) $validated['lat'],
            (float) $validated['lng'],
            (int) ($validated['limit'] ?? 5),
        );

        return response()->json([
            'origin' => [
                'lat' => (float) $validated['lat'],
                'lng' => (float) $validated['lng'],
            ],
            'recommended' => $facilities[0] ?? null,
            'facilities' => $facilities,
            'coverage' => $finder->coverageStats(),
            'india_mode' => true,
            'integration_note' => 'Production deployments can replace this India catalog with CPCB/SPCB, producer responsibility, or Places API providers via PLACES_API_KEY.',
        ]);
    }
}
