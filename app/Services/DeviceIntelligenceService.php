<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DeviceIntelligenceService
{
    public function analyze(?string $modelName, ?UploadedFile $image = null, string $condition = 'unknown'): array
    {
        $signal = trim(($modelName ?? '').' '.($image?->getClientOriginalName() ?? ''));
        $category = $this->detectCategory($signal);
        $profile = $this->profiles()[$category];
        $condition = $this->normalizeCondition($condition);

        $score = $this->ecoScore($profile, $condition, $signal, $image);
        $points = (int) round($profile['base_points'] * ($score / 80));
        $valueEstimate = $this->valueEstimateInr($profile, $condition);
        $weight = $profile['weight_kg'];
        $co2 = round($weight * $profile['co2_factor'], 2);
        $pollution = round($weight * (float) config('sustainability.impact_factors.pollution_prevented_multiplier', 1.8), 2);
        $water = (int) round($weight * (float) config('sustainability.impact_factors.water_protection_liters_per_kg', 740));

        return [
            'identified_model' => $this->friendlyModelName($modelName, $image, $profile),
            'category' => $category,
            'category_label' => $profile['label'],
            'recognition' => [
                'method' => $image ? 'image-and-text-estimation' : 'text-estimation',
                'confidence' => $this->confidence($signal, $image),
                'note' => $image
                    ? 'Image filename and upload metadata were used locally. Configure AI_IMAGE_RECOGNITION_ENDPOINT for production vision recognition.'
                    : 'Model keywords were matched against the local e-waste intelligence catalog.',
            ],
            'condition' => $condition,
            'eco_score' => $score,
            'points' => $points,
            'reward_breakdown' => [
                'base_points' => $profile['base_points'],
                'eco_score_bonus' => max(0, $points - $profile['base_points']),
                'certificate_bonus' => 30,
                'streak_bonus' => 15,
            ],
            'badge_preview' => $this->badgeForPoints($points),
            'repairability' => $profile['repairability'],
            'category_code' => $profile['category_code'],
            'estimated_recycling_value_inr' => $valueEstimate,
            'materials' => $profile['materials'],
            'hazards' => $profile['hazards'],
            'environmental_effects' => $this->environmentalEffects(),
            'health_effects' => $this->healthEffects(),
            'impact' => [
                'ewaste_kg' => $weight,
                'co2_kg' => $co2,
                'pollution_prevented_kg' => $pollution,
                'water_protected_liters' => $water,
                'landfill_space_liters' => (int) round($weight * 4.5),
            ],
            'recommendation' => $this->recommendation($condition, $profile),
            'india_compliance' => $this->indiaComplianceChecklist($condition, $profile),
            'prep_checklist' => $this->prepChecklist($condition, $profile),
            'tips' => $profile['tips'],
            'did_you_know' => $profile['facts'][array_rand($profile['facts'])],
            'quiz' => $this->quizFor($category),
            'statistics' => [
                [
                    'label' => 'Indicative recycling value',
                    'value' => 'INR '.$valueEstimate['min'].'-'.$valueEstimate['max'],
                    'detail' => 'A non-cash material estimate for awareness and reward scoring.',
                ],
                [
                    'label' => 'Recoverable metal streams',
                    'value' => count($profile['materials']),
                    'detail' => 'Estimated from category-level material composition.',
                ],
                [
                    'label' => 'Toxic risk level',
                    'value' => $profile['risk_level'],
                    'detail' => 'Based on battery, display, solder, and plastic content.',
                ],
                [
                    'label' => 'Suggested next step',
                    'value' => Str::headline($this->recommendation($condition, $profile)['primary_action']),
                    'detail' => 'Repair and donation are prioritized before recycling where possible.',
                ],
            ],
        ];
    }

    public function catalogSummary(): array
    {
        return collect($this->profiles())
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'],
                'examples' => $profile['examples'],
                'base_points' => $profile['base_points'],
            ])
            ->values()
            ->all();
    }

    private function detectCategory(string $signal): string
    {
        $normalized = Str::lower($signal);

        foreach ($this->profiles() as $category => $profile) {
            foreach ($profile['keywords'] as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    return $category;
                }
            }
        }

        return 'generic';
    }

    private function normalizeCondition(string $condition): string
    {
        $condition = Str::of($condition)->lower()->trim()->value();

        return match (true) {
            Str::contains($condition, ['working', 'good', 'usable']) => 'working',
            Str::contains($condition, ['minor', 'screen', 'repair']) => 'minor repair',
            Str::contains($condition, ['battery', 'swollen', 'heat']) => 'battery risk',
            Str::contains($condition, ['dead', 'broken', 'not working']) => 'not working',
            default => 'unknown',
        };
    }

    private function ecoScore(array $profile, string $condition, string $signal, ?UploadedFile $image): int
    {
        $score = $profile['eco_score_base'];
        $score += strlen(trim($signal)) > 8 ? 6 : 0;
        $score += $image ? 7 : 0;
        $score += match ($condition) {
            'working' => 12,
            'minor repair' => 8,
            'battery risk' => -5,
            'not working' => 2,
            default => 0,
        };

        return max(35, min(99, $score));
    }

    private function confidence(string $signal, ?UploadedFile $image): int
    {
        $confidence = strlen(trim($signal)) > 6 ? 74 : 52;
        $confidence += $image ? 12 : 0;

        return min(94, $confidence);
    }

    private function friendlyModelName(?string $modelName, ?UploadedFile $image, array $profile): string
    {
        if ($modelName && trim($modelName) !== '') {
            return Str::of($modelName)->squish()->limit(80, '')->value();
        }

        if ($image) {
            return Str::of(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                ->replace(['_', '-'], ' ')
                ->squish()
                ->title()
                ->value() ?: $profile['label'];
        }

        return $profile['label'];
    }

    private function recommendation(string $condition, array $profile): array
    {
        return match ($condition) {
            'working' => [
                'primary_action' => 'donate',
                'rationale' => 'The device appears usable, so donation through an India-based NGO, school, or refurbisher keeps it in service and delays new manufacturing demand.',
                'alternatives' => ['Sell to a refurbisher', 'Trade in through a producer take-back program', 'Recycle only if data wiping or repair fails'],
            ],
            'minor repair' => [
                'primary_action' => 'repair',
                'rationale' => 'A repair-first path usually saves more emissions than immediate material recovery and supports local repair jobs.',
                'alternatives' => ['Donate after repair', 'Recycle damaged batteries separately', 'Use certified data destruction'],
            ],
            'battery risk' => [
                'primary_action' => 'special handling',
                'rationale' => 'Battery swelling or overheating can cause fire risk; use a certified facility and avoid mailing loose batteries.',
                'alternatives' => ['Call the facility first', 'Tape exposed terminals', 'Do not puncture or compress the battery'],
            ],
            default => [
                'primary_action' => $profile['default_action'],
                'rationale' => 'Certified recycling can recover valuable materials while keeping toxins out of soil, air, and water.',
                'alternatives' => ['Check repairability', 'Remove personal data', 'Ask for a recycling certificate'],
            ],
        };
    }

    private function valueEstimateInr(array $profile, string $condition): array
    {
        $rateCard = [
            'Gold' => 6500,
            'Silver' => 80,
            'Copper' => 0.85,
            'Aluminum' => 0.22,
            'Rare earth metals' => 8,
        ];

        $raw = collect($profile['materials'])->sum(function (array $material) use ($rateCard) {
            $rate = $rateCard[$material['name']] ?? 0;

            return (float) $material['amount'] * $rate;
        });

        $recoveryFactor = match ($condition) {
            'working' => 0.48,
            'minor repair' => 0.42,
            'battery risk' => 0.26,
            'not working' => 0.34,
            default => 0.30,
        };

        $mid = max(25, (int) round($raw * $profile['value_factor'] * $recoveryFactor));

        return [
            'min' => max(10, (int) round($mid * 0.75)),
            'max' => max(25, (int) round($mid * 1.35)),
            'note' => 'Indicative material-awareness estimate, not a guaranteed buyback price.',
        ];
    }

    private function indiaComplianceChecklist(string $condition, array $profile): array
    {
        $items = [
            ['label' => 'Use authorized channel', 'detail' => 'Route the device to an authorized recycler, producer take-back desk, or verified collection partner.'],
            ['label' => 'Avoid informal burning or acid stripping', 'detail' => 'Informal recovery can release lead, mercury, cadmium, and plastic toxins.'],
            ['label' => 'Ask for proof', 'detail' => 'Keep a pickup receipt, recycling certificate, or QR verification for your records.'],
        ];

        if ($profile['contains_data']) {
            $items[] = ['label' => 'Protect personal data', 'detail' => 'Back up, sign out, factory reset, and request data destruction for storage devices.'];
        }

        if ($condition === 'battery risk' || $profile['battery_sensitive']) {
            $items[] = ['label' => 'Battery-safe handling', 'detail' => 'Do not crush or puncture batteries; tape terminals and use a battery-aware collection point.'];
        }

        return $items;
    }

    private function prepChecklist(string $condition, array $profile): array
    {
        $steps = [
            ['step' => 'Photograph device', 'detail' => 'Keep a simple record before drop-off or pickup.'],
            ['step' => 'Remove accessories', 'detail' => 'Separate chargers, SIM cards, memory cards, toner, and removable batteries where possible.'],
        ];

        if ($profile['contains_data']) {
            $steps[] = ['step' => 'Data wipe', 'detail' => 'Back up files, sign out of accounts, and run factory reset or disk wipe.'];
        }

        $steps[] = ['step' => 'Choose India facility', 'detail' => 'Pick the nearest center with certificate support, pickup, or data wiping as needed.'];

        if ($condition === 'working' || $condition === 'minor repair') {
            $steps[] = ['step' => 'Check reuse path', 'detail' => 'Repair or donation should be tried before material recycling.'];
        }

        $steps[] = ['step' => 'Collect certificate', 'detail' => 'Download the PDF certificate and keep the QR verification link.'];

        return $steps;
    }

    private function badgeForPoints(int $points): string
    {
        return match (true) {
            $points >= 220 => 'Recycling Champion',
            $points >= 140 => 'Eco Warrior',
            default => 'Green Starter',
        };
    }

    private function environmentalEffects(): array
    {
        return [
            ['label' => 'Soil pollution', 'detail' => 'Heavy metals can bind to soil and persist for years near informal dumps.'],
            ['label' => 'Water contamination', 'detail' => 'Rain can move lead, mercury, cadmium, and flame retardants into waterways.'],
            ['label' => 'Air pollution', 'detail' => 'Open burning releases fine particles, dioxins, and toxic plastic fumes.'],
            ['label' => 'Wildlife damage', 'detail' => 'Toxins can bioaccumulate through insects, fish, birds, and mammals.'],
        ];
    }

    private function healthEffects(): array
    {
        return [
            ['label' => 'Brain damage', 'detail' => 'Lead and mercury exposure can affect learning, memory, and development.'],
            ['label' => 'Kidney issues', 'detail' => 'Cadmium and lead exposure are linked with kidney stress and long-term organ damage.'],
            ['label' => 'Respiratory disease', 'detail' => 'Burning plastics and dust from dismantling can aggravate lungs and airways.'],
            ['label' => 'Nervous system damage', 'detail' => 'Mercury, arsenic, and solvent exposure can harm nerve function.'],
        ];
    }

    private function quizFor(string $category): array
    {
        return [
            'question' => "What is the safest next step for an old {$this->profiles()[$category]['label']}?",
            'options' => [
                ['value' => 'trash', 'label' => 'Put it in regular trash'],
                ['value' => 'burn', 'label' => 'Burn it to reduce volume'],
                ['value' => 'certified', 'label' => 'Use a certified repair, donation, or recycling channel'],
            ],
            'answer' => 'certified',
            'explanation' => 'Certified channels recover useful materials and control toxic exposure.',
        ];
    }

    private function profiles(): array
    {
        return [
            'smartphone' => [
                'label' => 'Smartphone',
                'keywords' => ['iphone', 'galaxy', 'pixel', 'oneplus', 'redmi', 'realme', 'vivo', 'oppo', 'phone', 'mobile'],
                'examples' => ['iPhone 11', 'Redmi Note 10', 'Samsung Galaxy'],
                'category_code' => 'ITEW-01',
                'weight_kg' => 0.19,
                'co2_factor' => 78,
                'eco_score_base' => 72,
                'base_points' => 125,
                'value_factor' => 1.05,
                'repairability' => 'Medium',
                'contains_data' => true,
                'battery_sensitive' => true,
                'risk_level' => 'High',
                'default_action' => 'recycle',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.034, 'unit' => 'g', 'use' => 'Circuit contacts and connectors'],
                    ['name' => 'Silver', 'amount' => 0.34, 'unit' => 'g', 'use' => 'Solder and conductive paths'],
                    ['name' => 'Copper', 'amount' => 15.0, 'unit' => 'g', 'use' => 'Wiring, coils, printed circuit boards'],
                    ['name' => 'Aluminum', 'amount' => 25.0, 'unit' => 'g', 'use' => 'Frame and casing'],
                    ['name' => 'Rare earth metals', 'amount' => 0.90, 'unit' => 'g', 'use' => 'Speakers, vibration motor, display'],
                ],
                'hazards' => $this->commonHazards(['Lithium' => 'Battery fire risk and groundwater contamination if punctured.']),
                'tips' => ['Back up and factory reset the phone.', 'Remove SIM and memory cards.', 'Recycle swollen batteries through special handling.'],
                'facts' => ['A phone contains dozens of elements, including small but valuable traces of gold and rare earth metals.', 'Repairing a phone screen can avoid much of the impact of manufacturing a replacement device.'],
            ],
            'laptop' => [
                'label' => 'Laptop',
                'keywords' => ['laptop', 'macbook', 'inspiron', 'thinkpad', 'vivobook', 'pavilion', 'notebook', 'chromebook'],
                'examples' => ['Dell Inspiron Laptop', 'HP Pavilion', 'Lenovo ThinkPad'],
                'category_code' => 'ITEW-02',
                'weight_kg' => 2.15,
                'co2_factor' => 42,
                'eco_score_base' => 78,
                'base_points' => 210,
                'value_factor' => 1.20,
                'repairability' => 'High',
                'contains_data' => true,
                'battery_sensitive' => true,
                'risk_level' => 'High',
                'default_action' => 'repair',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.22, 'unit' => 'g', 'use' => 'Motherboard contacts and ports'],
                    ['name' => 'Silver', 'amount' => 1.20, 'unit' => 'g', 'use' => 'Solder and circuitry'],
                    ['name' => 'Copper', 'amount' => 185.0, 'unit' => 'g', 'use' => 'Heat pipes, wiring, boards'],
                    ['name' => 'Aluminum', 'amount' => 620.0, 'unit' => 'g', 'use' => 'Body, hinges, heat spreaders'],
                    ['name' => 'Rare earth metals', 'amount' => 3.80, 'unit' => 'g', 'use' => 'Drive magnets, speakers, display'],
                ],
                'hazards' => $this->commonHazards(['Lithium' => 'Rechargeable battery packs require controlled handling.']),
                'tips' => ['Remove or wipe storage drives.', 'Try RAM or battery replacement before recycling.', 'Use a recycler that offers data destruction proof.'],
                'facts' => ['Laptop reuse often saves more impact than immediate recycling because the display, battery, and chips are energy-intensive to manufacture.', 'Copper and aluminum recovery from laptops can reduce demand for new mining.'],
            ],
            'tv' => [
                'label' => 'Television or Monitor',
                'keywords' => ['tv', 'television', 'monitor', 'display', 'lcd', 'led screen', 'oled'],
                'examples' => ['Samsung TV', 'LG Monitor', 'LED Television'],
                'category_code' => 'CEEW-01',
                'weight_kg' => 9.50,
                'co2_factor' => 21,
                'eco_score_base' => 80,
                'base_points' => 260,
                'value_factor' => 0.74,
                'repairability' => 'Medium',
                'contains_data' => false,
                'battery_sensitive' => false,
                'risk_level' => 'Severe',
                'default_action' => 'recycle',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.08, 'unit' => 'g', 'use' => 'Control boards and ports'],
                    ['name' => 'Silver', 'amount' => 0.90, 'unit' => 'g', 'use' => 'Conductive circuits'],
                    ['name' => 'Copper', 'amount' => 430.0, 'unit' => 'g', 'use' => 'Power supply and wiring'],
                    ['name' => 'Aluminum', 'amount' => 980.0, 'unit' => 'g', 'use' => 'Frame and heat sinks'],
                    ['name' => 'Rare earth metals', 'amount' => 5.10, 'unit' => 'g', 'use' => 'Display components and speakers'],
                ],
                'hazards' => $this->commonHazards(['Mercury' => 'Older backlights may contain mercury vapor that must not be crushed.']),
                'tips' => ['Do not break the screen glass.', 'Use a facility that accepts large displays.', 'Keep power cords bundled for safer handling.'],
                'facts' => ['Older flat-panel displays can contain mercury lamps, while CRT units can contain significant leaded glass.', 'Display recycling needs specialized handling because glass, plastics, and circuit boards separate differently.'],
            ],
            'tablet' => [
                'label' => 'Tablet',
                'keywords' => ['ipad', 'tablet', 'surface', 'tab'],
                'examples' => ['iPad', 'Samsung Tab', 'Lenovo Tab'],
                'category_code' => 'ITEW-03',
                'weight_kg' => 0.55,
                'co2_factor' => 62,
                'eco_score_base' => 74,
                'base_points' => 150,
                'value_factor' => 1.08,
                'repairability' => 'Medium',
                'contains_data' => true,
                'battery_sensitive' => true,
                'risk_level' => 'High',
                'default_action' => 'repair',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.055, 'unit' => 'g', 'use' => 'Logic board contacts'],
                    ['name' => 'Silver', 'amount' => 0.52, 'unit' => 'g', 'use' => 'Circuit pathways'],
                    ['name' => 'Copper', 'amount' => 42.0, 'unit' => 'g', 'use' => 'Battery tabs and boards'],
                    ['name' => 'Aluminum', 'amount' => 140.0, 'unit' => 'g', 'use' => 'Shell and frame'],
                    ['name' => 'Rare earth metals', 'amount' => 1.60, 'unit' => 'g', 'use' => 'Speakers and display'],
                ],
                'hazards' => $this->commonHazards(['Lithium' => 'Thin lithium polymer packs can be damaged during dismantling.']),
                'tips' => ['Disable account locks before donation.', 'Protect cracked glass before transport.', 'Ask about battery-safe drop-off bins.'],
                'facts' => ['Tablets pack batteries and displays into a thin shell, making certified dismantling important.', 'Donation can be a strong path when the tablet still receives security updates.'],
            ],
            'printer' => [
                'label' => 'Printer',
                'keywords' => ['printer', 'scanner', 'inkjet', 'laserjet', 'toner'],
                'examples' => ['HP LaserJet', 'Canon Printer', 'Epson Scanner'],
                'category_code' => 'ITEW-04',
                'weight_kg' => 6.20,
                'co2_factor' => 16,
                'eco_score_base' => 69,
                'base_points' => 180,
                'value_factor' => 0.72,
                'repairability' => 'Medium',
                'contains_data' => false,
                'battery_sensitive' => false,
                'risk_level' => 'Moderate',
                'default_action' => 'recycle',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.025, 'unit' => 'g', 'use' => 'Control boards'],
                    ['name' => 'Silver', 'amount' => 0.25, 'unit' => 'g', 'use' => 'Conductive contacts'],
                    ['name' => 'Copper', 'amount' => 115.0, 'unit' => 'g', 'use' => 'Motors and wiring'],
                    ['name' => 'Aluminum', 'amount' => 240.0, 'unit' => 'g', 'use' => 'Rails and internal parts'],
                    ['name' => 'Rare earth metals', 'amount' => 1.20, 'unit' => 'g', 'use' => 'Motors and sensors'],
                ],
                'hazards' => $this->commonHazards(['Plastic toxins' => 'Mixed plastics and toner residues should avoid open burning.']),
                'tips' => ['Remove ink or toner cartridges for separate return.', 'Bundle cables and trays.', 'Choose repair if only rollers or cartridges failed.'],
                'facts' => ['Printer cartridges need separate handling because toner and ink are not the same stream as electronics.', 'Motors inside printers are a useful source of copper recovery.'],
            ],
            'appliance' => [
                'label' => 'Home Appliance',
                'keywords' => ['washing machine', 'mixer', 'microwave', 'ac ', 'air conditioner', 'refrigerator', 'fridge', 'cooler', 'fan'],
                'examples' => ['Old mixer grinder', 'Microwave oven', 'Air conditioner'],
                'category_code' => 'LSEEW-01',
                'weight_kg' => 14.00,
                'co2_factor' => 18,
                'eco_score_base' => 76,
                'base_points' => 280,
                'value_factor' => 0.62,
                'repairability' => 'High',
                'contains_data' => false,
                'battery_sensitive' => false,
                'risk_level' => 'High',
                'default_action' => 'repair',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.02, 'unit' => 'g', 'use' => 'Control boards and sensors'],
                    ['name' => 'Silver', 'amount' => 0.35, 'unit' => 'g', 'use' => 'Contacts and control circuits'],
                    ['name' => 'Copper', 'amount' => 620.0, 'unit' => 'g', 'use' => 'Motors, wiring, compressors'],
                    ['name' => 'Aluminum', 'amount' => 1250.0, 'unit' => 'g', 'use' => 'Frames, coils, heat exchangers'],
                    ['name' => 'Rare earth metals', 'amount' => 2.20, 'unit' => 'g', 'use' => 'Motors and sensors'],
                ],
                'hazards' => $this->commonHazards(['Plastic toxins' => 'Insulation, foams, and mixed plastics need controlled dismantling.']),
                'tips' => ['Try repair before disposal.', 'Do not remove compressor or wiring at home.', 'Ask the facility if doorstep pickup is available.'],
                'facts' => ['Large appliances often contain recoverable copper and aluminum, making proper routing especially useful.', 'Repairing appliances can avoid heavy transport and manufacturing emissions.'],
            ],
            'battery' => [
                'label' => 'Battery or Power Bank',
                'keywords' => ['battery', 'power bank', 'ups', 'inverter battery', 'lithium battery'],
                'examples' => ['Power bank', 'UPS battery', 'Laptop battery'],
                'category_code' => 'BATT-01',
                'weight_kg' => 0.65,
                'co2_factor' => 35,
                'eco_score_base' => 82,
                'base_points' => 170,
                'value_factor' => 0.55,
                'repairability' => 'Low',
                'contains_data' => false,
                'battery_sensitive' => true,
                'risk_level' => 'Severe',
                'default_action' => 'special handling',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.00, 'unit' => 'g', 'use' => 'Minimal or none'],
                    ['name' => 'Silver', 'amount' => 0.02, 'unit' => 'g', 'use' => 'Protection circuit contacts'],
                    ['name' => 'Copper', 'amount' => 45.0, 'unit' => 'g', 'use' => 'Tabs and wiring'],
                    ['name' => 'Aluminum', 'amount' => 85.0, 'unit' => 'g', 'use' => 'Cell casing and foils'],
                    ['name' => 'Rare earth metals', 'amount' => 0.15, 'unit' => 'g', 'use' => 'Small control components'],
                ],
                'hazards' => $this->commonHazards(['Lithium' => 'Damaged cells can overheat, ignite, and release reactive compounds.']),
                'tips' => ['Tape exposed terminals.', 'Keep away from heat and water.', 'Use only battery-aware collection points.'],
                'facts' => ['Lithium battery recycling needs fire-safe storage and specialist handling.', 'Throwing batteries in mixed waste increases fire risk during transport and sorting.'],
            ],
            'generic' => [
                'label' => 'Electronic Device',
                'keywords' => [],
                'examples' => ['Router', 'Speaker', 'Camera'],
                'category_code' => 'GEN-01',
                'weight_kg' => 1.20,
                'co2_factor' => 30,
                'eco_score_base' => 65,
                'base_points' => 100,
                'value_factor' => 0.85,
                'repairability' => 'Medium',
                'contains_data' => false,
                'battery_sensitive' => true,
                'risk_level' => 'Moderate',
                'default_action' => 'recycle',
                'materials' => [
                    ['name' => 'Gold', 'amount' => 0.03, 'unit' => 'g', 'use' => 'Circuit boards and connectors'],
                    ['name' => 'Silver', 'amount' => 0.25, 'unit' => 'g', 'use' => 'Solder and contacts'],
                    ['name' => 'Copper', 'amount' => 55.0, 'unit' => 'g', 'use' => 'Wires, motors, boards'],
                    ['name' => 'Aluminum', 'amount' => 90.0, 'unit' => 'g', 'use' => 'Casing and heat sinks'],
                    ['name' => 'Rare earth metals', 'amount' => 0.80, 'unit' => 'g', 'use' => 'Magnets, speakers, sensors'],
                ],
                'hazards' => $this->commonHazards(),
                'tips' => ['Do not mix electronics with household trash.', 'Keep batteries separate where possible.', 'Ask for a certificate after drop-off.'],
                'facts' => ['Even small electronics can contain recoverable metals and plastic additives that should not enter landfills.', 'Sorting devices before drop-off improves recycling quality.'],
            ],
        ];
    }

    private function commonHazards(array $overrides = []): array
    {
        $hazards = [
            'Lead' => 'Used in solder and older glass; can damage brain development and contaminate soil.',
            'Mercury' => 'Can appear in lamps, switches, and displays; accumulates in waterways and fish.',
            'Cadmium' => 'Found in some batteries and components; linked with kidney and bone damage.',
            'Lithium' => 'Batteries can ignite if crushed and can leach reactive compounds.',
            'Arsenic' => 'Used in some semiconductors; unsafe disposal can expose workers and soil.',
            'Plastic toxins' => 'Flame retardants and PVC can release toxic fumes when burned.',
        ];

        foreach ($overrides as $name => $detail) {
            $hazards[$name] = $detail;
        }

        return collect($hazards)
            ->map(fn (string $detail, string $name) => [
                'name' => $name,
                'severity' => in_array($name, ['Lead', 'Mercury', 'Cadmium', 'Lithium'], true) ? 'High' : 'Moderate',
                'detail' => $detail,
            ])
            ->values()
            ->all();
    }
}
