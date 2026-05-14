<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\DeviceIntelligenceService;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;

class SustainabilityController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboard,
        DeviceIntelligenceService $devices,
        FacilityFinder $facilities,
    ) {
        return view('sustainability.index', [
            'dashboard' => $dashboard->snapshot($request->session()->getId()),
            'deviceCatalog' => $devices->catalogSummary(),
            'facilityPreview' => array_slice($facilities->all(), 0, 5),
            'cityPresets' => $facilities->cityPresets(),
            'coverageStats' => $facilities->coverageStats(),
        ]);
    }
}
