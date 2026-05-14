<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Models\RecyclingActivity;
use App\Models\RecyclingCertificate;
use App\Services\DeviceIntelligenceService;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecyclingController extends Controller
{
    public function complete(Request $request, DeviceIntelligenceService $devices, FacilityFinder $facilities)
    {
        $validated = $request->validate([
            'model_name' => ['required', 'string', 'max:120'],
            'condition' => ['nullable', 'string', 'max:60'],
            'holder_name' => ['nullable', 'string', 'max:80'],
            'facility_id' => ['nullable', 'string', 'max:80'],
        ]);

        $analysis = $devices->analyze($validated['model_name'], null, $validated['condition'] ?? 'unknown');
        $facility = isset($validated['facility_id']) ? $facilities->find($validated['facility_id']) : null;
        $sessionId = $request->session()->getId();

        $activity = RecyclingActivity::create([
            'session_id' => $sessionId,
            'device_model' => $analysis['identified_model'],
            'device_category' => $analysis['category_label'],
            'condition' => $analysis['condition'],
            'recommended_action' => $analysis['recommendation']['primary_action'],
            'eco_score' => $analysis['eco_score'],
            'points_awarded' => $analysis['points'],
            'ewaste_kg' => $analysis['impact']['ewaste_kg'],
            'co2_kg' => $analysis['impact']['co2_kg'],
            'pollution_prevented_kg' => $analysis['impact']['pollution_prevented_kg'],
            'materials_recovered' => $analysis['materials'],
            'hazards' => $analysis['hazards'],
            'facility' => $facility,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $verificationToken = Str::upper(Str::random(16));
        $certificate = RecyclingCertificate::create([
            'recycling_activity_id' => $activity->id,
            'session_id' => $sessionId,
            'certificate_number' => 'ECO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'holder_name' => $validated['holder_name'] ?? 'Eco Recycler',
            'verification_token' => $verificationToken,
            'qr_payload' => route('certificates.verify', $verificationToken),
            'impact_summary' => [
                'device' => $activity->device_model,
                'category' => $activity->device_category,
                'eco_score' => $activity->eco_score,
                'points' => $activity->points_awarded,
                'estimated_value_inr' => $analysis['estimated_recycling_value_inr'],
                'ewaste_kg' => $activity->ewaste_kg,
                'co2_kg' => $activity->co2_kg,
                'pollution_prevented_kg' => $activity->pollution_prevented_kg,
                'facility' => $facility['name'] ?? 'Verified recycling partner',
            ],
            'issued_at' => now(),
        ]);

        return response()->json([
            'activity' => $activity,
            'certificate' => [
                'number' => $certificate->certificate_number,
                'download_url' => route('certificates.download', $certificate),
                'verify_url' => route('certificates.verify', $certificate->verification_token),
                'share_text' => "I recycled {$activity->device_model} through an India e-waste workflow and reduced {$activity->co2_kg} kg CO2 with EcoCycle Smart.",
            ],
            'wallet' => [
                'points_added' => $activity->points_awarded,
            ],
        ], 201);
    }
}
