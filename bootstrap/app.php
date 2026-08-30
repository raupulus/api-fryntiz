<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // === Webhooks ===
            Route::middleware('api')
                ->prefix('')
                ->group(base_path('routes/webhook.php'));

            // === Rutas web de módulos ===
            Route::middleware('web')
                ->prefix('hardware')
                ->group(base_path('routes/hardware/web.php'));

            Route::middleware('web')
                ->prefix('keycounter')
                ->group(base_path('routes/keycounter/web.php'));

            Route::middleware('web')
                ->prefix('smartplant')
                ->group(base_path('routes/smart_plant/web.php'));

            Route::middleware('web')
                ->prefix('weatherstation')
                ->group(base_path('routes/weather_station/web.php'));

            Route::middleware('web')
                ->prefix('airflight')
                ->group(base_path('routes/airflight/web.php'));

            Route::middleware('web')
                ->prefix('cv')
                ->group(base_path('routes/cv/web.php'));

            // === API V2 (nueva versión) ===
            Route::middleware('api')
                ->prefix('api/v2')
                ->group(base_path('routes/api/v2.php'));
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS global basado en config/cors.php (fix_10 fase 02).
        $middleware->prepend(HandleCors::class);

        // Proxies de confianza.
        //
        // Sin esto, detrás de nginx/Cloudflare `$request->ip()` devuelve la IP
        // del proxy, así que TODOS los rate limit por IP (login, contacto,
        // newsletter) pasan a ser un cupo global compartido por todos los
        // visitantes: ni frenan a un atacante ni dejan pasar el tráfico bueno.
        //
        // Por defecto se confía en los rangos privados (la red de Docker y el
        // nginx del propio host), no en "*": si la aplicación quedara alcanzable
        // directamente, "*" permitiría falsificar la IP con una cabecera.
        $middleware->trustProxies(
            at: array_values(array_filter(array_map('trim', explode(',', (string) env(
                'TRUSTED_PROXIES',
                '127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'
            ))))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Sólo los alias que usa alguna ruta.
        //
        // Había tres más —`cors`, `check.domain` e `ip.counter.strict`— que no
        // los pedía ninguna ruta y que, de haberlos pedido, habrían hecho daño:
        // el `Cors` propio devolvía `Access-Control-Allow-Origin` con el origen
        // que mandara el cliente (o sea, cualquiera), `DomainCheckMiddleware`
        // hacía `return $next($request)` en su primera línea con la comprobación
        // entera detrás como código muerto, y `IpCounterStrict` era un limitador
        // a mano cuya ventana nunca se cerraba porque cada petición le renovaba
        // el TTL. CORS lo lleva `HandleCors` con `config/cors.php`, y los
        // límites de peticiones, los rate limiters de `config/rate_limits.php`.
        $middleware->alias([
            // Sanctum: control de abilities por token de dispositivo IoT (fix_1 fase 05).
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // El idioma de la respuesta sale de `Accept-Language` (o de `?lang=`).
        //
        // Las webs que consumen esta API no comparten sesión con ella: cada
        // petición llega sola. Sin esto la API responde siempre en español, así
        // que un formulario en inglés enseña los errores de validación en
        // español.
        $middleware->api(prepend: [SetLocale::class]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                ], 401);
            }
        });

        // JsonValidationException y JsonAuthorizationException se auto-renderizan
        // mediante su propio método render() (estructura JSON estándar de la API V2).

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para realizar esta acción',
                ], 403);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para realizar esta acción',
                ], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/v1/*') || $request->is('api/*/v1/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La API V1 está obsoleta y ha sido eliminada. Por favor, actualice sus clientes a la API V2.',
                ], 410);
            }
            if ($request->is('api/v2/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'API V2 - Endpoint no encontrado',
                ], 404);
            }
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Método no permitido',
                ], 405);
            }
        });
    })
    ->create();
