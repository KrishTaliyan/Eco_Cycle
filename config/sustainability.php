<?php

return [
    'integrations' => [
        'reverse_geocoding_url' => env('REVERSE_GEOCODING_URL', 'https://nominatim.openstreetmap.org/reverse'),
        'places_api_key' => env('PLACES_API_KEY'),
        'ai_image_recognition_endpoint' => env('AI_IMAGE_RECOGNITION_ENDPOINT'),
        'ai_image_recognition_key' => env('AI_IMAGE_RECOGNITION_KEY'),
        'email_provider' => env('MAIL_MAILER', 'log'),
    ],

    'impact_factors' => [
        'urban_travel_speed_kmh' => 28,
        'pollution_prevented_multiplier' => 1.8,
        'water_protection_liters_per_kg' => 740,
    ],
];
