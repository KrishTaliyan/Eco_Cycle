<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function facilities(FacilityFinder $facilities)
    {
        return view('sustainability.facilities', $this->facilityData($facilities));
    }

    public function pickup(FacilityFinder $facilities)
    {
        return view('sustainability.pickup', $this->facilityData($facilities));
    }

    public function learn()
    {
        return view('sustainability.learn');
    }

    public function rewards(Request $request, DashboardService $dashboard)
    {
        return view('sustainability.rewards', [
            'dashboard' => $dashboard->snapshot($request->session()->getId(), $request->user()),
        ]);
    }

    public function about(FacilityFinder $facilities)
    {
        return view('sustainability.about', [
            'coverageStats' => $facilities->coverageStats(),
        ]);
    }

    public function contact()
    {
        return view('sustainability.contact');
    }

    public function terms()
    {
        return view('sustainability.terms');
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        return back()->with('status', 'Thanks, '.$validated['name'].'. Your message has been logged for the EcoCycle team.');
    }

    private function facilityData(FacilityFinder $facilities): array
    {
        return [
            'cityPresets' => $facilities->cityPresets(),
            'coverageStats' => $facilities->coverageStats(),
            'facilityPreview' => array_slice($facilities->all(), 0, 6),
        ];
    }
}
