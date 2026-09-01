<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Si la prueba se ha autenticado con `actingAs()` / `be()`.
     */
    private bool $autenticadoEnMemoria = false;

    /**
     * Las pruebas de la API encadenan varias peticiones con tokens distintos, y
     * el guard `sanctum` se queda con el usuario que resolvió la primera vez:
     * sin olvidarlo, la segunda petición sigue viéndose como el primer token y
     * las comprobaciones de permisos dan un resultado que no es el real.
     *
     * Antes esto se resolvía olvidando los guards antes de CADA petición, sin
     * excepciones, y eso arrasaba también con el usuario que acababa de poner
     * `actingAs()`. Como en las pruebas la sesión es `array`, no había de dónde
     * recuperarlo: toda ruta con middleware `auth` respondía 302 al login por
     * muy autenticada que estuviera la prueba, así que ninguna ruta web privada
     * se podía probar —y no había ni una sola prueba de ese tipo—.
     *
     * Ahora se olvidan los guards sólo cuando la autenticación no viene de
     * `actingAs()`, que es justo el caso de las pruebas por token.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (! $this->autenticadoEnMemoria) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Punto por el que pasan `actingAs()` y `be()`.
     */
    public function be(UserContract $user, $guard = null)
    {
        $this->autenticadoEnMemoria = true;

        return parent::be($user, $guard);
    }
}
