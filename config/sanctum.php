<?php

declare(strict_types=1);

use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    /*
     * En .env se ponen sólo los dominios, separados por comas y sin esquema:
     *
     *   SANCTUM_STATEFUL_DOMAINS=raupulus.dev,laguialinux.com
     *
     * De cada uno se derivan aquí las variantes que hacen falta. Fuera de
     * producción se añaden los localhost habituales; en producción NO, para no
     * dejar puesto un dominio de desarrollo con cookies de sesión.
     */
    'stateful' => (static function (): array {
        $domains = array_values(array_filter(array_map(
            static fn (string $domain): string => trim($domain),
            explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', ''))
        )));

        $host = parse_url((string) env('APP_URL'), PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            $domains[] = $host;
        }

        if (env('APP_ENV') !== 'production') {
            $domains = array_merge($domains, [
                'localhost',
                'localhost:3000',
                'localhost:5173',
                'localhost:8000',
                '127.0.0.1',
                '127.0.0.1:8000',
                '::1',
            ]);
        }

        return array_values(array_unique($domains));
    })(),

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
    ],

];
