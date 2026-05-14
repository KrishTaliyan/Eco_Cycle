<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Services\DeviceIntelligenceService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function analyze(Request $request, DeviceIntelligenceService $devices)
    {
        $validated = $request->validate([
            'model_name' => ['nullable', 'string', 'max:120'],
            'condition' => ['nullable', 'string', 'max:60'],
            'device_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if (blank($validated['model_name'] ?? null) && ! $request->hasFile('device_image')) {
            return response()->json([
                'message' => 'Enter a model name or upload a device image to analyze.',
            ], 422);
        }

        return response()->json([
            'analysis' => $devices->analyze(
                $validated['model_name'] ?? null,
                $request->file('device_image'),
                $validated['condition'] ?? 'unknown',
            ),
        ]);
    }
}
