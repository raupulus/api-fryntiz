<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Api Raupulus'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'api_url' => env('API_URL', env('APP_URL', 'http://localhost:8000').'/api'),
    'asset_url' => env('ASSET_URL'),
    /*
    |--------------------------------------------------------------------------
    | Zona horaria
    |--------------------------------------------------------------------------
    |
    | 'UTC' LITERAL, no `env('APP_TIMEZONE')`, y a propósito (D100): la
    | aplicación corre y guarda siempre en UTC. Los cacharros mandan hora local
    | y se convierte al guardar. Si esto saliera de una variable de entorno, un
    | despliegue con la variable distinta empezaría a guardar horas desplazadas
    | y no se notaría hasta mirar una gráfica meses después.
    |
    | `display_timezone` es sólo para MOSTRAR: el panel de Filament. Ni la base
    | de datos ni la salida de la API cambian — la API va en UTC con `Z`.
    |
    */

    'timezone' => 'UTC',

    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Europe/Madrid'),
    'locale' => 'es',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
    ],

    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'),
];
