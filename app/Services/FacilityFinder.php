<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FacilityFinder
{
    public function nearest(float $latitude, float $longitude, int $limit = 5): array
    {
        return $this->facilityCollection()
            ->map(fn (array $facility) => $this->decorateFacility($facility, $latitude, $longitude))
            ->sortBy([
                ['distance_km', 'asc'],
                ['match_score', 'desc'],
            ])
            ->take($limit)
            ->values()
            ->all();
    }

    public function find(string $id): ?array
    {
        return $this->facilityCollection()->firstWhere('id', $id);
    }

    public function all(): array
    {
        return $this->facilityCollection()->values()->all();
    }

    public function cityPresets(): array
    {
        return [
            ['city' => 'Delhi NCR', 'state' => 'Delhi', 'lat' => 28.6139, 'lng' => 77.2090],
            ['city' => 'Mumbai', 'state' => 'Maharashtra', 'lat' => 19.0760, 'lng' => 72.8777],
            ['city' => 'Bengaluru', 'state' => 'Karnataka', 'lat' => 12.9716, 'lng' => 77.5946],
            ['city' => 'Hyderabad', 'state' => 'Telangana', 'lat' => 17.3850, 'lng' => 78.4867],
            ['city' => 'Chennai', 'state' => 'Tamil Nadu', 'lat' => 13.0827, 'lng' => 80.2707],
            ['city' => 'Kolkata', 'state' => 'West Bengal', 'lat' => 22.5726, 'lng' => 88.3639],
            ['city' => 'Pune', 'state' => 'Maharashtra', 'lat' => 18.5204, 'lng' => 73.8567],
            ['city' => 'Ahmedabad', 'state' => 'Gujarat', 'lat' => 23.0225, 'lng' => 72.5714],
            ['city' => 'Jaipur', 'state' => 'Rajasthan', 'lat' => 26.9124, 'lng' => 75.7873],
            ['city' => 'Kochi', 'state' => 'Kerala', 'lat' => 9.9312, 'lng' => 76.2673],
            ['city' => 'Indore', 'state' => 'Madhya Pradesh', 'lat' => 22.7196, 'lng' => 75.8577],
            ['city' => 'Guwahati', 'state' => 'Assam', 'lat' => 26.1445, 'lng' => 91.7362],
        ];
    }

    public function coverageStats(): array
    {
        $facilities = $this->facilityCollection();

        return [
            'centers' => $facilities->count(),
            'states' => $facilities->pluck('state')->unique()->count(),
            'pickup_enabled' => $facilities->where('pickup_available', true)->count(),
            'certificate_enabled' => $facilities->where('certificate_supported', true)->count(),
            'map_embed_url' => $this->indiaMapEmbedUrl(),
        ];
    }

    public function indiaMapEmbedUrl(): string
    {
        return 'https://www.openstreetmap.org/export/embed.html?bbox=67.2%2C6.5%2C97.5%2C37.6&layer=mapnik&marker=22.5937%2C78.9629';
    }

    private function decorateFacility(array $facility, float $latitude, float $longitude): array
    {
        $distance = $this->distanceInKm($latitude, $longitude, $facility['lat'], $facility['lng']);
        $status = $this->statusFor($facility);
        $speed = (float) config('sustainability.impact_factors.urban_travel_speed_kmh', 28);
        $minutes = max(6, (int) round(($distance / $speed) * 60));

        return array_merge($facility, [
            'distance_km' => round($distance, 2),
            'travel_time_minutes' => $minutes,
            'travel_time_label' => $this->formatTravelTime($minutes),
            'open_status' => $status,
            'match_score' => $this->matchScore($facility, $distance),
            'directions_url' => sprintf(
                'https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=%s%%2C%s%%3B%s%%2C%s',
                $latitude,
                $longitude,
                $facility['lat'],
                $facility['lng'],
            ),
            'map_embed_url' => $this->mapEmbedUrl($facility),
        ]);
    }

    private function matchScore(array $facility, float $distance): int
    {
        $score = 100 - min(70, (int) round($distance / 20));
        $score += $facility['pickup_available'] ? 8 : 0;
        $score += $facility['certificate_supported'] ? 8 : 0;
        $score += $facility['data_wipe'] ? 6 : 0;
        $score += $facility['battery_handling'] ? 6 : 0;

        return max(10, min(100, $score));
    }

    private function statusFor(array $facility): array
    {
        $now = CarbonImmutable::now($facility['timezone']);
        $day = strtolower($now->format('D'));
        $hours = $facility['hours'][$day] ?? 'closed';

        if ($hours === 'closed') {
            return [
                'is_open' => false,
                'label' => 'Closed today',
                'detail' => 'Next working day pickup or drop-off available.',
            ];
        }

        [$start, $end] = explode('-', $hours);
        $current = $now->format('H:i');
        $isOpen = $current >= $start && $current <= $end;

        return [
            'is_open' => $isOpen,
            'label' => $isOpen ? 'Open now' : 'Closed now',
            'detail' => sprintf('Today: %s to %s IST', $this->humanTime($start), $this->humanTime($end)),
        ];
    }

    private function humanTime(string $time): string
    {
        return CarbonImmutable::createFromFormat('H:i', $time)->format('g:i A');
    }

    private function formatTravelTime(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? "{$hours} hr {$remaining} min" : "{$hours} hr";
    }

    private function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function mapEmbedUrl(array $facility): string
    {
        $lat = $facility['lat'];
        $lng = $facility['lng'];
        $padding = 0.12;

        return sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%s%%2C%s%%2C%s%%2C%s&layer=mapnik&marker=%s%%2C%s',
            $lng - $padding,
            $lat - $padding,
            $lng + $padding,
            $lat + $padding,
            $lat,
            $lng,
        );
    }

    private function facilityCollection(): Collection
    {
        return collect([
            $this->facility('delhi-attero-okhla', 'Attero Authorized Collection Desk', 'Okhla Industrial Area, New Delhi', 'Delhi NCR', 'Delhi', 'North', 28.5355, 77.2777, ['Mobiles', 'Laptops', 'Appliances', 'Batteries'], ['Material recovery', 'Battery recycling', 'Bulk collection', 'Certificate support'], true, true, true, true, 'Best for IT assets, batteries, and office pickups'),
            $this->facility('noida-bulk-recovery', 'Noida E-Waste Bulk Recovery Hub', 'Sector 63, Noida, Uttar Pradesh', 'Noida', 'Uttar Pradesh', 'North', 28.6270, 77.3750, ['Servers', 'Computers', 'Printers', 'Networking'], ['Corporate pickup', 'Asset audit', 'Data wiping', 'Chain of custody'], true, true, true, true, 'Best for offices, colleges, and bulk IT disposal'),
            $this->facility('mumbai-eco-reco-andheri', 'EcoReco Collection Partner', 'Andheri East, Mumbai, Maharashtra', 'Mumbai', 'Maharashtra', 'West', 19.1155, 72.8727, ['Phones', 'Laptops', 'TVs', 'Batteries'], ['Authorized recycling', 'Pickup scheduling', 'Data wiping', 'Certificate support'], true, true, true, true, 'Best for personal electronics and apartment drives'),
            $this->facility('pune-karo-sambhav', 'Pune Producer Take-Back Desk', 'Shivajinagar, Pune, Maharashtra', 'Pune', 'Maharashtra', 'West', 18.5308, 73.8475, ['Mobiles', 'Tablets', 'Chargers', 'Small appliances'], ['Producer responsibility return', 'Repair referral', 'Awareness drives'], true, true, false, true, 'Best for brand take-back and campus collection'),
            $this->facility('ahmedabad-circularity-desk', 'Ahmedabad Circular Electronics Center', 'Prahlad Nagar, Ahmedabad, Gujarat', 'Ahmedabad', 'Gujarat', 'West', 23.0120, 72.5108, ['Laptops', 'Monitors', 'Phones', 'Cables'], ['Sorting', 'Material recovery', 'Donation routing'], true, true, false, true, 'Best for household electronics and reuse screening'),
            $this->facility('jaipur-green-it-point', 'Jaipur Green IT Collection Point', 'Malviya Nagar, Jaipur, Rajasthan', 'Jaipur', 'Rajasthan', 'North', 26.8505, 75.8069, ['Computers', 'Printers', 'Batteries', 'Phones'], ['Drop-off', 'Battery handling', 'Repair partner routing'], false, true, false, true, 'Best for student and home device recycling'),
            $this->facility('bengaluru-saahas-koramangala', 'Saahas Zero Waste E-Waste Desk', 'Koramangala, Bengaluru, Karnataka', 'Bengaluru', 'Karnataka', 'South', 12.9352, 77.6245, ['Phones', 'Laptops', 'Chargers', 'Small appliances'], ['Responsible collection', 'Awareness programs', 'Bulk pickups', 'Certificate support'], true, true, true, true, 'Best for apartments, schools, and repair-first sorting'),
            $this->facility('bengaluru-peenya-recovery', 'Peenya Electronics Recovery Cluster', 'Peenya Industrial Area, Bengaluru, Karnataka', 'Bengaluru', 'Karnataka', 'South', 13.0285, 77.5197, ['TVs', 'Computers', 'Servers', 'Batteries'], ['Material recovery', 'Dismantling', 'Battery isolation'], true, true, true, true, 'Best for large appliances and IT hardware'),
            $this->facility('chennai-takeback-guindy', 'Chennai Electronics Take-Back Center', 'Guindy, Chennai, Tamil Nadu', 'Chennai', 'Tamil Nadu', 'South', 13.0108, 80.2206, ['Mobiles', 'Laptops', 'TVs', 'Printers'], ['Drop-off', 'Producer take-back', 'Certificate support'], true, true, false, true, 'Best for mixed household electronics'),
            $this->facility('coimbatore-reuse-recycle', 'Coimbatore Reuse and Recycle Desk', 'Peelamedu, Coimbatore, Tamil Nadu', 'Coimbatore', 'Tamil Nadu', 'South', 11.0310, 77.0383, ['Small appliances', 'Chargers', 'Phones', 'Laptops'], ['Reuse screening', 'Donation routing', 'Drop-off'], false, true, false, false, 'Best for repairable electronics'),
            $this->facility('hyderabad-madhapur-drop', 'Hyderabad Responsible Electronics Drop-Off', 'Madhapur, Hyderabad, Telangana', 'Hyderabad', 'Telangana', 'South', 17.4483, 78.3915, ['Phones', 'Laptops', 'Tablets', 'TV accessories'], ['Retail take-back', 'Certificate request', 'Sorting support'], true, true, true, true, 'Best for quick drop-off and data wipe requests'),
            $this->facility('vijayawada-smart-collection', 'Vijayawada Smart Collection Point', 'Benz Circle, Vijayawada, Andhra Pradesh', 'Vijayawada', 'Andhra Pradesh', 'South', 16.5016, 80.6480, ['Phones', 'Cables', 'Printers', 'Routers'], ['Municipal collection', 'Awareness drives', 'Repair referral'], false, true, false, true, 'Best for neighborhood collection drives'),
            $this->facility('kolkata-hulladek-desk', 'Kolkata E-Waste Action Desk', 'Salt Lake Sector V, Kolkata, West Bengal', 'Kolkata', 'West Bengal', 'East', 22.5768, 88.4335, ['Computers', 'Mobiles', 'Cables', 'Appliances'], ['Doorstep pickup', 'Institution drives', 'Certificate support'], true, true, false, true, 'Best for housing societies and offices'),
            $this->facility('bhubaneswar-clean-tech', 'Bhubaneswar Clean Tech Collection', 'Jayadev Vihar, Bhubaneswar, Odisha', 'Bhubaneswar', 'Odisha', 'East', 20.2961, 85.8245, ['Phones', 'Laptops', 'Printers', 'Chargers'], ['Drop-off', 'Sorting', 'Bulk request'], true, true, false, false, 'Best for small electronics and community drives'),
            $this->facility('kochi-clean-kerala-desk', 'Clean Kerala E-Waste Collection Desk', 'Kakkanad, Kochi, Kerala', 'Kochi', 'Kerala', 'South', 10.0159, 76.3419, ['Phones', 'Laptops', 'Small appliances', 'Batteries'], ['Public collection', 'Battery handling', 'Certificate support'], true, true, false, true, 'Best for civic collection and battery-safe routing'),
            $this->facility('lucknow-gomti-green', 'Lucknow Green Electronics Desk', 'Gomti Nagar, Lucknow, Uttar Pradesh', 'Lucknow', 'Uttar Pradesh', 'North', 26.8467, 81.0076, ['Computers', 'Phones', 'Printers', 'Cables'], ['Drop-off', 'Pickup request', 'Awareness drives'], true, true, false, false, 'Best for home and small office recycling'),
            $this->facility('indore-smart-city-ewaste', 'Indore Smart City E-Waste Point', 'Vijay Nagar, Indore, Madhya Pradesh', 'Indore', 'Madhya Pradesh', 'Central', 22.7533, 75.8937, ['Mobiles', 'Laptops', 'Chargers', 'Small appliances'], ['Municipal routing', 'Drop-off', 'Repair referral'], false, true, false, true, 'Best for household collection and repairable devices'),
            $this->facility('nagpur-orange-city-recycle', 'Nagpur Orange City Recycle Desk', 'Sitabuldi, Nagpur, Maharashtra', 'Nagpur', 'Maharashtra', 'Central', 21.1458, 79.0882, ['Computers', 'Routers', 'Phones', 'Batteries'], ['Drop-off', 'Battery handling', 'Certificate request'], true, true, false, true, 'Best for central India routing and battery-safe collection'),
            $this->facility('chandigarh-green-desk', 'Chandigarh Green Electronics Desk', 'Industrial Area Phase I, Chandigarh', 'Chandigarh', 'Chandigarh', 'North', 30.7046, 76.8013, ['Laptops', 'Phones', 'Printers', 'Cables'], ['Drop-off', 'Pickup request', 'Data wipe referral'], true, true, true, false, 'Best for government and education sector devices'),
            $this->facility('guwahati-ne-recycle', 'Guwahati North-East E-Waste Desk', 'GS Road, Guwahati, Assam', 'Guwahati', 'Assam', 'North East', 26.1445, 91.7362, ['Phones', 'Laptops', 'Cables', 'Small appliances'], ['Regional aggregation', 'Drop-off', 'Awareness drives'], false, true, false, false, 'Best for North-East community collection'),
        ]);
    }

    private function facility(
        string $id,
        string $name,
        string $address,
        string $city,
        string $state,
        string $zone,
        float $lat,
        float $lng,
        array $accepted,
        array $services,
        bool $pickup,
        bool $certificate,
        bool $dataWipe,
        bool $batteryHandling,
        string $bestFor,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zone' => $zone,
            'lat' => $lat,
            'lng' => $lng,
            'timezone' => 'Asia/Kolkata',
            'phone' => '+91 1800 000 3939',
            'accepted' => $accepted,
            'services' => $services,
            'pickup_available' => $pickup,
            'certificate_supported' => $certificate,
            'data_wipe' => $dataWipe,
            'battery_handling' => $batteryHandling,
            'best_for' => $bestFor,
            'compliance' => 'E-Waste Management Rules aligned workflow with authorized recycler routing',
            'capacity_kg_day' => $pickup ? 850 : 280,
            'price_note' => 'Drop-off is free for common household electronics; bulk pickups may need scheduling.',
            'hours' => $this->weekdayHours('09:30', '18:30', saturday: '10:00-16:00'),
        ];
    }

    private function weekdayHours(string $start, string $end, ?string $saturday = null, ?string $sunday = null): array
    {
        $weekday = "{$start}-{$end}";

        return [
            'mon' => $weekday,
            'tue' => $weekday,
            'wed' => $weekday,
            'thu' => $weekday,
            'fri' => $weekday,
            'sat' => $saturday ?? 'closed',
            'sun' => $sunday ?? 'closed',
        ];
    }
}
