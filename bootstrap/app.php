<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // === API V1 (existentes — compatibilidad) ===
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api/v1.php'));

            Route::middleware('api')
                ->prefix('api/hardware/v1')
                ->group(base_path('routes/hardware/v1.php'));

            Route::middleware('api')
                ->prefix('api/keycounter/v1')
                ->group(base_path('routes/keycounter/v1.php'));

            Route::middleware('api')
                ->prefix('api/smart_plant/v1')
                ->group(base_path('routes/smart_plant/v1.php'));

            Route::middleware('api')
                ->prefix('api/weatherstation/v1')
                ->group(base_path('routes/weather_station/v1.php'));

            Route::middleware('api')
                ->prefix('api/airflight/v1')
                ->group(base_path('routes/airflight/v1.php'));

            Route::middleware('api')
                ->prefix('api/cv/v1')
                ->group(base_path('routes/cv/v1.php'));

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
        $middleware->alias([
            'cors' => \App\Http\Middleware\Cors::class,
            'cors.allow.all' => \App\Http\Middleware\CorsAllowAll::class,
            'check.domain' => \App\Http\Middleware\DomainCheckMiddleware::class,
            'ip.counter.strict' => \App\Http\Middleware\IpCounterStrict::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                ], 401);
            }
        });

        $exceptions->render(function (\App\Exceptions\JsonValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\App\Exceptions\JsonAuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para realizar esta accion',
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso no encontrado',
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metodo no permitido',
                ], 405);
            }
        });
    })
    ->create();
