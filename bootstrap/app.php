<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\CorsAllowAll;
use App\Http\Middleware\DomainCheckMiddleware;
use App\Http\Middleware\IpCounterStrict;
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

            // === Dashboard (panel legacy AdminLTE) ===
            Route::middleware('web')
                ->prefix('dashboard')
                ->group(base_path('routes/dashboard.php'));

            // === API V2 (nueva versión) ===
            Route::middleware('api')
                ->prefix('api/v2')
                ->group(base_path('routes/api/v2.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS global basado en config/cors.php (fix_10 fase 02).
        $middleware->prepend(HandleCors::class);

        $middleware->alias([
            'cors' => Cors::class,
            'cors.allow.all' => CorsAllowAll::class,
            'check.domain' => DomainCheckMiddleware::class,
            'ip.counter.strict' => IpCounterStrict::class,
            // Sanctum: control de abilities por token de dispositivo IoT (fix_1 fase 05).
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

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

        // JsonValidationException y JsonAuthorizationException se auto-renderizadas
        // mediante su propio método render() para mantener compatibilidad con API V1.

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para realizar esta accion',
                ], 403);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para realizar esta accion',
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
                    'message' => 'Metodo no permitido',
                ], 405);
            }
        });
    })
    ->create();
