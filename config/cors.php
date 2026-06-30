<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configurado para api-fryntiz: los endpoints públicos de la API V2
    | (p. ej. /api/v2/airflight/aircrafts, invocado desde el navegador en
    | /airflight) deben responder con cabeceras CORS válidas. `api/*` cubre
    | toda la API V2, por lo que no hacen falta entradas por módulo/versión.
    |
    */

    'paths' => [
        'api/*',
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
