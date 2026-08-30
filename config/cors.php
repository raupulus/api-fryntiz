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

    /*
     * El comodín de localhost sólo existe fuera de producción. Estaba puesto
     * de forma permanente, y con `supports_credentials => true` eso significa
     * que cualquier página servida desde localhost —incluida una que abra el
     * navegador de la víctima— podía hacer peticiones con cookies contra la
     * API de producción (fix1 #9).
     */
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,
];
