<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca una respuesta como «no indexar».
 *
 * `public/robots.txt` ya tiene `Disallow: /admin`, `/panel` y `/login`, pero
 * eso pide que **no se rastree**, que no es lo mismo que no indexar: un
 * buscador que llegue por un enlace externo puede listar la URL sin haber
 * abierto la página, y precisamente el `Disallow` le impide leer un
 * `<meta name="robots">` que estuviera dentro. Para que una página no se
 * indexe, el buscador tiene que poder ver la directiva.
 *
 * Va como cabecera HTTP y no sólo como `<meta>` porque así vale para todo lo
 * que sirve el panel, no sólo para el HTML: descargas, respuestas de Livewire
 * y ficheros adjuntos incluidos.
 *
 * `noarchive` va con las otras dos para que tampoco se guarde una copia en
 * caché del formulario de acceso.
 */
class NoIndex
{
    public const VALOR = 'noindex, nofollow, noarchive';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Sin pisar lo que una respuesta concreta haya decidido por su cuenta:
        // `CurriculumController::shared()` pone su propio `X-Robots-Tag` y esa
        // decisión es suya.
        if (! $response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', self::VALOR);
        }

        return $response;
    }
}
