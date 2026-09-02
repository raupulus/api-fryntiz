<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        /*
        |----------------------------------------------------------------------
        | Respuestas de error de la API — un solo sitio con la forma
        |----------------------------------------------------------------------
        |
        | Todo lo de aquí pasa por `JsonHelper`, que es la puerta estática de
        | `App\Support\Http\ApiEnvelope`. Su gemelo para controladores es
        | `App\Traits\ApiResponseTrait`.
        |
        | Antes cada uno de estos handlers escribía `['success' => false, ...]`
        | a mano: ocho copias sólo en este fichero, once contando la ruta de
        | cierre y los `render()` de `app/Exceptions/`. Once sitios que había
        | que tocar a la vez el día que el envelope cambiara, y once donde uno
        | se podía quedar sin actualizar sin que nada avisara.
        |
        | Los mensajes salen de `lang/{es,en}/api.php`, así que responden en el
        | idioma que pida el cliente (`SetLocale`).
        |
        */

        $esApi = static fn ($request): bool => $request->is('api/*') || $request->wantsJson();

        $exceptions->render(function (AuthenticationException $e, $request) use ($esApi) {
            if ($esApi($request)) {
                return JsonHelper::unauthorized(__('api.unauthenticated'));
            }
        });

        // JsonValidationException y JsonAuthorizationException se auto-renderizan
        // mediante su propio método render(), que también llama a JsonHelper.

        $exceptions->render(function (AuthorizationException $e, $request) use ($esApi) {
            if ($esApi($request)) {
                return JsonHelper::forbidden(__('api.forbidden'));
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) use ($esApi) {
            if ($esApi($request)) {
                return JsonHelper::forbidden(__('api.forbidden'));
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) use ($esApi) {
            if ($esApi($request)) {
                return JsonHelper::notFound(__('api.not_found'));
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) use ($esApi) {
            if ($request->is('api/v1/*') || $request->is('api/*/v1/*')) {
                return JsonHelper::error(__('api.v1_gone'), 410);
            }
            if ($request->is('api/v2/*')) {
                return JsonHelper::notFound(__('api.endpoint_not_found'));
            }
            if ($esApi($request)) {
                return JsonHelper::notFound(__('api.not_found'));
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) use ($esApi) {
            if ($esApi($request)) {
                return JsonHelper::error(__('api.method_not_allowed'), 405);
            }
        });

        /*
        |----------------------------------------------------------------------
        | Cierre: TODO lo demás que reviente en la API sale con el envelope
        |----------------------------------------------------------------------
        |
        | Va el último a propósito: los `render()` se prueban en orden de
        | registro y gana el primero que devuelva algo, así que los específicos
        | de arriba siguen mandando sobre éste.
        |
        | Sin este bloque, todo lo que no tuviera su handler propio salía con la
        | forma de Laravel —`{"message": "...", "exception": ..., "trace": [...]}`—
        | o directamente en HTML si el cliente no mandaba
        | `Accept: application/json`. Y eso es justo lo que hace un
        | microcontrolador: manda el comodín de tipos, o no manda `Accept`.
        |
        | Los dos casos que más se notaban (auditoría AR-E02, verificados
        | provocándolos):
        |
        |   · **429** del throttle. Lo dispara cualquiera insistiendo, y con
        |     APP_DEBUG=true venía con el stack trace entero y rutas absolutas
        |     del sistema de ficheros.
        |   · **500** de un fallo no controlado.
        |
        | El código HTTP se respeta si la excepción trae uno (429, 413, 419…);
        | cualquier otra cosa es un 500. Las cabeceras de la excepción HTTP se
        | conservan: sin ellas, un 429 perdería su `Retry-After` y el cliente no
        | sabría cuánto esperar.
        |
        | El detalle de la excepción NUNCA viaja en el mensaje: va dentro del
        | bloque `debug`, que sólo existe con APP_DEBUG=true.
        |
        */
        $exceptions->render(function (Throwable $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $esHttp = $e instanceof HttpExceptionInterface;
            $status = $esHttp ? $e->getStatusCode() : 500;

            $message = match (true) {
                $e instanceof ThrottleRequestsException => __('api.too_many_requests'),
                $status === 413 => __('api.payload_too_large'),
                default => __('api.server_error'),
            };

            $response = JsonHelper::serverError($message, $e, $status);

            return $esHttp ? $response->withHeaders($e->getHeaders()) : $response;
        });
    })
    ->create();
