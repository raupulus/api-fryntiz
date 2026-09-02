<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use Illuminate\Routing\Route as RutaLaravel;
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
     * @return list<RutaLaravel>
     */
    private function rutasDeLaApi(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            static fn (RutaLaravel $ruta): bool => str_starts_with($ruta->uri(), 'api/')
        ));
    }

    /**
     * Middleware efectivo de una ruta, con los grupos ya expandidos.
     *
     * `$ruta->middleware()` devuelve `['api']` sin desplegar, así que un
     * `throttle` declarado sobre el grupo no se vería. `gatherMiddleware()` sí
     * lo despliega, que es lo que de verdad corre.
     *
     * @return list<string>
     */
    private function middlewareDe(RutaLaravel $ruta): array
    {
        return array_values(array_map(
            static fn ($m): string => is_string($m) ? $m : '',
            Route::gatherRouteMiddleware($ruta)
        ));
    }

    private function tieneThrottle(RutaLaravel $ruta): bool
    {
        foreach ($this->middlewareDe($ruta) as $middleware) {
            if (str_contains(mb_strtolower($middleware), 'throttle')) {
                return true;
            }
        }

        return false;
    }

    #[Test]
    public function todas_las_rutas_de_la_api_tienen_limite_de_peticiones(): void
    {
        $sinLimite = [];

        foreach ($this->rutasDeLaApi() as $ruta) {
            if (! $this->tieneThrottle($ruta)) {
                $sinLimite[] = implode('|', $ruta->methods()).' /'.$ruta->uri();
            }
        }

        $this->assertSame(
            [],
            $sinLimite,
            "Estas rutas de la API no tienen ningún límite de peticiones:\n  ".
            implode("\n  ", $sinLimite)."\n\n".
            'El grupo `api` trae un techo (`throttle:api-global`, en bootstrap/app.php). '.
            'Si una ruta aparece aquí es que está fuera del grupo o que el techo se ha quitado.'
        );
    }

    #[Test]
    public function hay_rutas_de_la_api_registradas(): void
    {
        // Red de seguridad del test de arriba: si el enrutador devolviera una
        // lista vacía, aquel pasaría sin comprobar nada.
        $this->assertGreaterThan(40, count($this->rutasDeLaApi()));
    }

    #[Test]
    public function toda_escritura_de_la_api_exige_autenticacion(): void
    {
        // Excepciones conscientes, todas públicas por diseño y todas con su
        // propio limitador declarado en la ruta:
        //
        //  · contact-messages        formulario de contacto, con reCAPTCHA
        //  · newsletter/**           alta y baja por token del correo
        //  · auth/tokens             es el login: no puede exigir estar dentro
        //  · api/v2/{any}            la ruta de cierre, que responde 404 a todo
        $publicas = [
            'api/v2/contact-messages',
            'api/v2/auth/tokens',
            'api/v2/{any}',
        ];

        $sinAuth = [];

        foreach ($this->rutasDeLaApi() as $ruta) {
            $metodos = array_diff($ruta->methods(), ['GET', 'HEAD', 'OPTIONS']);

            if ($metodos === []) {
                continue;
            }

            if (in_array($ruta->uri(), $publicas, true) || str_starts_with($ruta->uri(), 'api/v2/newsletter/')) {
                continue;
            }

            $autenticada = false;

            foreach ($this->middlewareDe($ruta) as $middleware) {
                if (str_contains($middleware, 'Authenticate') || str_starts_with($middleware, 'auth:')) {
                    $autenticada = true;
                }
            }

            if (! $autenticada) {
                $sinAuth[] = implode('|', $metodos).' /'.$ruta->uri();
            }
        }

        $this->assertSame(
            [],
            $sinAuth,
            "Estas escrituras de la API no exigen autenticación:\n  ".implode("\n  ", $sinAuth)
        );
    }

    #[Test]
    public function las_escrituras_iot_exigen_una_ability_de_modulo(): void
    {
        // La ability es lo que acota un token robado a su módulo. Una escritura
        // IoT con `auth:sanctum` pero sin `ability:` la alcanzaría cualquier
        // token, incluido el de una estación meteorológica (auditoría A3).
        $modulos = ['hardware', 'weather-stations', 'keycounter', 'smartplant', 'airflight'];
        $sinAbility = [];

        foreach ($this->rutasDeLaApi() as $ruta) {
            $metodos = array_diff($ruta->methods(), ['GET', 'HEAD', 'OPTIONS']);
            $esDeModulo = false;

            foreach ($modulos as $modulo) {
                if (str_starts_with($ruta->uri(), 'api/v2/'.$modulo)) {
                    $esDeModulo = true;
                }
            }

            if ($metodos === [] || ! $esDeModulo) {
                continue;
            }

            $conAbility = false;

            foreach ($this->middlewareDe($ruta) as $middleware) {
                if (str_contains($middleware, 'Abilit')) {
                    $conAbility = true;
                }
            }

            if (! $conAbility) {
                $sinAbility[] = implode('|', $metodos).' /'.$ruta->uri();
            }
        }

        $this->assertSame(
            [],
            $sinAbility,
            "Estas escrituras IoT no exigen ninguna ability:\n  ".implode("\n  ", $sinAbility)
        );
    }
}
