<?php

namespace App\Services;

use App\Models\RecyclingActivity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function snapshot(?string $sessionId): array
    {
        $allActivities = RecyclingActivity::query()->latest('completed_at')->get();
        $userActivities = $sessionId
            ? $allActivities->where('session_id', $sessionId)->values()
            : collect();

        return [
            'totals' => $this->totals($allActivities),
            'user' => $this->userStats($userActivities),
            'india_impact' => $this->indiaImpact($allActivities),
            'materials' => $this->materials($allActivities),
            'monthly' => $this->monthly($allActivities),
            'leaderboard' => $this->leaderboard($allActivities),
            'state_ranking' => $this->stateRanking($allActivities),
            'challenges' => $this->challenges($userActivities),
            'coupons' => $this->coupons((int) $userActivities->sum('points_awarded')),
            'recent_activity' => $userActivities->take(6)->values()->map(fn (RecyclingActivity $activity) => [
                'device' => $activity->device_model,
                'category' => $activity->device_category,
                'points' => $activity->points_awarded,
                'impact' => "{$activity->co2_kg} kg CO2 reduced",
                'date' => $activity->completed_at?->format('M j, Y') ?? $activity->created_at->format('M j, Y'),
            ])->all(),
        ];
    }

    private function totals(Collection $activities): array
    {
        return [
            'devices' => $activities->count(),
            'ewaste_kg' => round((float) $activities->sum('ewaste_kg'), 2),
            'pollution_prevented_kg' => round((float) $activities->sum('pollution_prevented_kg'), 2),
            'co2_kg' => round((float) $activities->sum('co2_kg'), 2),
            'points' => (int) $activities->sum('points_awarded'),
        ];
    }

    private function indiaImpact(Collection $activities): array
    {
        $ewaste = (float) $activities->sum('ewaste_kg');
        $co2 = (float) $activities->sum('co2_kg');

        return [
            [
                'label' => 'Landfill volume avoided',
                'value' => round($ewaste * 4.5, 1),
                'unit' => 'liters',
                'detail' => 'Estimated space saved by certified diversion.',
            ],
            [
                'label' => 'Water protected',
                'value' => round($ewaste * 740),
                'unit' => 'liters',
                'detail' => 'Awareness estimate based on safe handling of toxic components.',
            ],
            [
                'label' => 'Metro commute equivalent',
                'value' => max(0, (int) round($co2 / 0.09)),
                'unit' => 'km',
                'detail' => 'Approximate low-carbon commute equivalent for user motivation.',
            ],
        ];
    }

    private function userStats(Collection $activities): array
    {
        $points = (int) $activities->sum('points_awarded');

        return [
            'devices' => $activities->count(),
            'points' => $points,
            'badges' => $this->badges($points),
            'streak_days' => $this->streak($activities),
            'ewaste_kg' => round((float) $activities->sum('ewaste_kg'), 2),
            'co2_kg' => round((float) $activities->sum('co2_kg'), 2),
        ];
    }

    private function materials(Collection $activities): array
    {
        $materials = [];

        foreach ($activities as $activity) {
            foreach ($activity->materials_recovered ?? [] as $material) {
                $name = $material['name'];
                $materials[$name] ??= ['name' => $name, 'amount' => 0, 'unit' => $material['unit']];
                $materials[$name]['amount'] += (float) $material['amount'];
            }
        }

        if ($materials === []) {
            $materials = [
                'Gold' => ['name' => 'Gold', 'amount' => 0, 'unit' => 'g'],
                'Silver' => ['name' => 'Silver', 'amount' => 0, 'unit' => 'g'],
                'Copper' => ['name' => 'Copper', 'amount' => 0, 'unit' => 'g'],
                'Aluminum' => ['name' => 'Aluminum', 'amount' => 0, 'unit' => 'g'],
                'Rare earth metals' => ['name' => 'Rare earth metals', 'amount' => 0, 'unit' => 'g'],
            ];
        }

        return collect($materials)
            ->map(fn (array $material) => array_merge($material, [
                'amount' => round((float) $material['amount'], 2),
            ]))
            ->values()
            ->all();
    }

    private function monthly(Collection $activities): array
    {
        return collect(range(5, 0))
            ->map(function (int $monthsBack) use ($activities) {
                $month = CarbonImmutable::now()->subMonths($monthsBack);
                $matching = $activities->filter(fn (RecyclingActivity $activity) => $activity->completed_at?->format('Y-m') === $month->format('Y-m'));

                return [
                    'label' => $month->format('M'),
                    'devices' => $matching->count(),
                    'ewaste_kg' => round((float) $matching->sum('ewaste_kg'), 2),
                    'co2_kg' => round((float) $matching->sum('co2_kg'), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function leaderboard(Collection $activities): array
    {
        $realRows = $activities
            ->groupBy('session_id')
            ->map(fn (Collection $rows, string $sessionId) => [
                'name' => $sessionId === session()->getId() ? 'You' : 'Community recycler '.substr($sessionId, 0, 4),
                'points' => (int) $rows->sum('points_awarded'),
                'devices' => $rows->count(),
            ])
            ->values();

        return $realRows
            ->concat([
                ['name' => 'Mumbai Society Drive', 'points' => 1240, 'devices' => 11],
                ['name' => 'Bengaluru Repair Circle', 'points' => 980, 'devices' => 8],
                ['name' => 'Delhi Campus Green Club', 'points' => 860, 'devices' => 7],
                ['name' => 'Kolkata Collection Team', 'points' => 640, 'devices' => 5],
            ])
            ->sortByDesc('points')
            ->take(5)
            ->values()
            ->all();
    }

    private function stateRanking(Collection $activities): array
    {
        $realRows = $activities
            ->map(function (RecyclingActivity $activity) {
                $facility = $activity->facility ?? [];

                return [
                    'state' => $facility['state'] ?? 'Your State',
                    'points' => $activity->points_awarded,
                    'devices' => 1,
                ];
            })
            ->groupBy('state')
            ->map(fn (Collection $rows, string $state) => [
                'state' => $state,
                'points' => (int) $rows->sum('points'),
                'devices' => (int) $rows->sum('devices'),
            ])
            ->values();

        return $realRows
            ->concat([
                ['state' => 'Maharashtra', 'points' => 1860, 'devices' => 16],
                ['state' => 'Karnataka', 'points' => 1640, 'devices' => 13],
                ['state' => 'Delhi NCR', 'points' => 1420, 'devices' => 12],
                ['state' => 'Tamil Nadu', 'points' => 1180, 'devices' => 10],
                ['state' => 'West Bengal', 'points' => 940, 'devices' => 8],
            ])
            ->sortByDesc('points')
            ->take(6)
            ->values()
            ->all();
    }

    private function challenges(Collection $activities): array
    {
        $completedToday = $activities->contains(fn (RecyclingActivity $activity) => $activity->completed_at?->isToday());

        return [
            [
                'title' => 'Locate one India collection center',
                'points' => 25,
                'status' => 'available',
                'progress' => 35,
            ],
            [
                'title' => 'Recycle, repair, or donate one device',
                'points' => 100,
                'status' => $completedToday ? 'completed' : 'available',
                'progress' => $completedToday ? 100 : 20,
            ],
            [
                'title' => 'Start a society or campus e-waste drive',
                'points' => 150,
                'status' => 'available',
                'progress' => 15,
            ],
            [
                'title' => 'Share QR certificate with a friend',
                'points' => 60,
                'status' => 'available',
                'progress' => 10,
            ],
        ];
    }

    private function coupons(int $points): array
    {
        return [
            ['title' => 'INR 100 repair coupon', 'cost' => 150, 'available' => $points >= 150],
            ['title' => 'Free data wipe voucher', 'cost' => 320, 'available' => $points >= 320],
            ['title' => 'Society pickup priority pass', 'cost' => 500, 'available' => $points >= 500],
            ['title' => 'Eco-store partner reward', 'cost' => 750, 'available' => $points >= 750],
        ];
    }

    private function badges(int $points): array
    {
        return [
            ['name' => 'Green Starter', 'earned' => $points >= 50, 'threshold' => 50],
            ['name' => 'Swachh Recycler', 'earned' => $points >= 220, 'threshold' => 220],
            ['name' => 'Eco Warrior', 'earned' => $points >= 450, 'threshold' => 450],
            ['name' => 'Recycling Champion', 'earned' => $points >= 900, 'threshold' => 900],
        ];
    }

    private function streak(Collection $activities): int
    {
        $dates = $activities
            ->pluck('completed_at')
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        $cursor = CarbonImmutable::today();

        while ($dates->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
