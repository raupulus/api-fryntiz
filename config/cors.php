<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configurado para api-fryntiz tras fix_10 / fase 02: el endpoint público
    | /api/airflight/v1/get/aircrafts/json debe responder con cabeceras CORS
    | válidas al ser invocado desde el navegador en /airflight.
    |
    */

    'paths' => [
        'api/*',
        'api/v1/*',
        'api/v2/*',
        'api/airflight/v1/*',
        'api/airflight/v2/*',
        'api/hardware/v1/*',
        'api/keycounter/v1/*',
        'api/smartplant/v1/*',
        'api/weatherstation/v1/*',
        'api/cv/v1/*',
        'sanctum/csrf-cookie',
        'login', 'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URLS', ''))
    ))),

    'allowed_origins_patterns' => [
        // Permite localhost / 127.0.0.1 con cualquier puerto en desarrollo.
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,
];
