<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeviceIntelligenceService;
use App\Services\FacilityFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __invoke(Request $request, FacilityFinder $facilities, DeviceIntelligenceService $devices)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $query = Str::lower(trim($validated['q'] ?? ''));
        $facilityRows = collect($facilities->all())
            ->filter(fn (array $facility) => $query === ''
                || Str::contains(Str::lower($facility['name'].' '.$facility['city'].' '.$facility['state'].' '.implode(' ', $facility['services'])), $query))
            ->take(6)
            ->map(fn (array $facility) => [
                'type' => 'facility',
                'title' => $facility['name'],
                'subtitle' => $facility['city'].', '.$facility['state'],
                'url' => route('facilities'),
                'icon' => 'map-pin',
            ]);

        $deviceRows = collect($devices->catalogSummary())
            ->filter(fn (array $device) => $query === ''
                || Str::contains(Str::lower($device['label'].' '.implode(' ', $device['examples'])), $query))
            ->take(5)
            ->map(fn (array $device) => [
                'type' => 'device',
                'title' => $device['label'],
                'subtitle' => implode(', ', array_slice($device['examples'], 0, 3)),
                'url' => route('sustainability.index').'#deviceForm',
                'icon' => 'scan-line',
            ]);

        $actionRows = collect([
            ['title' => 'Plan pickup', 'subtitle' => 'Prepare a doorstep or society pickup', 'url' => route('pickup'), 'icon' => 'truck'],
            ['title' => 'Open rewards', 'subtitle' => 'Badges, coupons, and leaderboard', 'url' => route('rewards'), 'icon' => 'trophy'],
            ['title' => 'Safety guide', 'subtitle' => 'Battery, data, and disposal prep', 'url' => route('learn'), 'icon' => 'shield-alert'],
            ['title' => 'Account settings', 'subtitle' => 'Theme, notifications, and preferences', 'url' => route('settings'), 'icon' => 'settings'],
        ])->filter(fn (array $row) => $query === ''
            || Str::contains(Str::lower($row['title'].' '.$row['subtitle']), $query))
            ->map(fn (array $row) => array_merge(['type' => 'action'], $row));

        return response()->json([
            'data' => $facilityRows
                ->concat($deviceRows)
                ->concat($actionRows)
                ->take(12)
                ->values(),
        ]);
    }
}
