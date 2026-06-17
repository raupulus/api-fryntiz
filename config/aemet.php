<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de la API AEMET OpenData
    |--------------------------------------------------------------------------
    | Documentación oficial: https://opendata.aemet.es/dist/index.html
    */

    'AEMET_API_KEY' => env('AEMET_API_KEY'),
    'api_key' => env('AEMET_API_KEY', ''),
    'base_url' => env('AEMET_BASE_URL', 'https://opendata.aemet.es/opendata/api'),

    /*
    | Códigos de localización por defecto.
    */
    'default_municipio' => env('AEMET_DEFAULT_MUNICIPIO', '11015'), // Chipiona
    'default_playa' => env('AEMET_DEFAULT_PLAYA', '1101501'),
    'default_costa' => env('AEMET_DEFAULT_COSTA', '11'),
    'default_area' => env('AEMET_DEFAULT_AREA', '61'),

    /*
    | Límites de rate-limit (info: AEMET aplica ~100 req/min y ~3000/día por API key).
    | Usamos un sliding window propio para no acercarnos al límite.
    */
    'rate_limit' => [
        'max_requests_per_minute' => 50,
        'max_requests_per_day' => 2000,
        'retry_attempts' => 3,
        'retry_base_delay_ms' => 1000,
    ],

    /*
    | TTL de la caché por tipo de endpoint (segundos).
    */
    'cache_ttl' => [
        'daily_prediction' => 600,      // 10 min
        'contamination' => 1800,        // 30 min
        'adverse_events' => 300,        // 5 min — los avisos son críticos
        'coast' => 1800,
        'high_sea' => 1800,
        'ozone' => 3600,                // 1h
        'sun_radiation' => 3600,
        'prediction_beach' => 1800,
    ],
];
