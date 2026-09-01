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
        // A dónde mandar a un invitado que choca con `auth` fuera de la API.
        //
        // Aquí no existe una vista de login genérica de Fortify (sólo hay
        // reset/verify): el login real siempre pasa por Filament. Sin esto,
        // el guard por defecto cae a `route('login')` —la ruta de Fortify—
        // que revienta con BindingResolutionException porque nadie registró
        // `Fortify::loginView()`. El panel Tenant es el login abierto a
        // cualquier usuario activo, admins incluidos.
        $middleware->redirectGuestsTo(fn () => route('filament.tenant.auth.login'));

        // CORS global basado en config/cors.php (fix_10 fase 02).
        $middleware->prepend(HandleCors::class);

        // Los proxies de confianza NO se configuran aquí: este callback corre
        // como `afterResolving` del Kernel, antes de que exista el servicio
        // `config`, así que sólo podría leerse con `env()` —y `env()` devuelve
        // `null` en el servidor, donde el despliegue hace `config:cache` y
        // Laravel ya no carga el `.env`. Se configura en
        // `AppServiceProvider::boot()`, que sí corre con la config cargada y
        // antes del pipeline de middleware. Ver `config/app.php`.

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
