<?php

return [
    'route' => [
        'default_average_speed_kmh' => (float) env('DEFAULT_AVERAGE_SPEED_KMH', 25),
    ],

    'routing' => [
        'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),
        'ors_api_key' => env('ORS_API_KEY'),
        'cache_seconds' => (int) env('ROUTING_CACHE_SECONDS', 60),
    ],
];
