<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Invariantes del contrato de la API, comprobadas sobre el enrutador.
 *
 * Un test por ruta envejece mal: cada endpoint nuevo hay que acordarse de
 * añadirlo. Esto recorre lo que hay registrado y comprueba las reglas que
 * valen para todas, así que una ruta nueva queda cubierta el día que se
 * escribe, sin tocar nada aquí.
 *
 * Existe porque la auditoría de 2026-09-02 encontró **catorce rutas públicas
 * sin ningún límite de peticiones** (AR-S01) y siete autenticadas en la misma
 * situación (AR-A01), con 389 tests en verde. Un test como éste las habría
 * cazado el día que entraron.
 */
class RouteContractTest extends TestCase
{
    /**
     * Rutas registradas bajo `api/`.
     *
     * @return list<LaravelRoute>
     */
    private function apiRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            static fn (LaravelRoute $route): bool => str_starts_with($route->uri(), 'api/')
        ));
    }

    /**
     * Middleware efectivo de una ruta, con los grupos ya expandidos.
     *
     * `$route->middleware()` devuelve `['api']` sin desplegar, así que un
     * `throttle` declarado sobre el grupo no se vería. `gatherMiddleware()` sí
     * lo despliega, que es lo que de verdad corre.
     *
     * @return list<string>
     */
    private function middlewareOf(LaravelRoute $route): array
    {
        return array_values(array_map(
            static fn ($m): string => is_string($m) ? $m : '',
            Route::gatherRouteMiddleware($route)
        ));
    }

    private function hasThrottle(LaravelRoute $route): bool
    {
        foreach ($this->middlewareOf($route) as $middleware) {
            if (str_contains(mb_strtolower($middleware), 'throttle')) {
                return true;
            }
        }

        return false;
    }

    #[Test]
    public function all_api_routes_have_a_rate_limit(): void
    {
        $withoutLimit = [];

        foreach ($this->apiRoutes() as $route) {
            if (! $this->hasThrottle($route)) {
                $withoutLimit[] = implode('|', $route->methods()).' /'.$route->uri();
            }
        }

        $this->assertSame(
            [],
            $withoutLimit,
            "Estas rutas de la API no tienen ningún límite de peticiones:\n  ".
            implode("\n  ", $withoutLimit)."\n\n".
            'El grupo `api` trae un techo (`throttle:api-global`, en bootstrap/app.php). '.
            'Si una ruta aparece aquí es que está fuera del grupo o que el techo se ha quitado.'
        );
    }

    #[Test]
    public function there_are_registered_api_routes(): void
    {
        // Red de seguridad del test de arriba: si el enrutador devolviera una
        // lista vacía, aquel pasaría sin comprobar nada.
        $this->assertGreaterThan(40, count($this->apiRoutes()));
    }

    #[Test]
    public function every_api_write_requires_authentication(): void
    {
        // Excepciones conscientes, todas públicas por diseño y todas con su
        // propio limitador declarado en la ruta:
        //
        //  · contact-messages        formulario de contacto, con reCAPTCHA
        //  · newsletter/**           alta y baja por token del correo
        //  · auth/tokens             es el login: no puede exigir estar dentro
        //  · api/v2/{any}            la ruta de cierre, que responde 404 a todo
        $publicRoutes = [
            'api/v2/contact-messages',
            'api/v2/auth/tokens',
            'api/v2/{any}',
        ];

        $withoutAuth = [];

        foreach ($this->apiRoutes() as $route) {
            $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);

            if ($methods === []) {
                continue;
            }

            if (in_array($route->uri(), $publicRoutes, true) || str_starts_with($route->uri(), 'api/v2/newsletter/')) {
                continue;
            }

            $isAuthenticated = false;

            foreach ($this->middlewareOf($route) as $middleware) {
                if (str_contains($middleware, 'Authenticate') || str_starts_with($middleware, 'auth:')) {
                    $isAuthenticated = true;
                }
            }

            if (! $isAuthenticated) {
                $withoutAuth[] = implode('|', $methods).' /'.$route->uri();
            }
        }

        $this->assertSame(
            [],
            $withoutAuth,
            "Estas escrituras de la API no exigen autenticación:\n  ".implode("\n  ", $withoutAuth)
        );
    }

    #[Test]
    public function iot_writes_require_a_module_ability(): void
    {
        // La ability es lo que acota un token robado a su módulo. Una escritura
        // IoT con `auth:sanctum` pero sin `ability:` la alcanzaría cualquier
        // token, incluido el de una estación meteorológica (auditoría A3).
        $modules = ['hardware', 'weather-stations', 'keycounter', 'smartplant', 'airflight'];
        $withoutAbility = [];

        foreach ($this->apiRoutes() as $route) {
            $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);
            $isModuleRoute = false;

            foreach ($modules as $module) {
                if (str_starts_with($route->uri(), 'api/v2/'.$module)) {
                    $isModuleRoute = true;
                }
            }

            if ($methods === [] || ! $isModuleRoute) {
                continue;
            }

            $hasAbility = false;

            foreach ($this->middlewareOf($route) as $middleware) {
                if (str_contains($middleware, 'Abilit')) {
                    $hasAbility = true;
                }
            }

            if (! $hasAbility) {
                $withoutAbility[] = implode('|', $methods).' /'.$route->uri();
            }
        }

        $this->assertSame(
            [],
            $withoutAbility,
            "Estas escrituras IoT no exigen ninguna ability:\n  ".implode("\n  ", $withoutAbility)
        );
    }
}
