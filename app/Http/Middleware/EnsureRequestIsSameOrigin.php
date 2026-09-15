<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta las rutas JSON de un widget propio (weather_station, airflight) a
 * quien pide la página que las consume — no a quien copia la URL del panel de
 * red del navegador y la reutiliza desde fuera.
 *
 * Estas rutas viven a propósito en el bloque `web`, sin token y cacheadas,
 * para no gastar la ability `weatherstation:read`/`airflight:read` en el
 * propio widget (ver `WeatherStationController::widget()`). Sin token no hay
 * nada que compruebe quién llama, así que la única señal disponible es de
 * dónde dice venir la petición: `Origin` (que `fetch({mode:'cors'})` manda
 * siempre, lo pida o no el mismo origen) o, si falta, `Referer` (que manda
 * cualquier fetch de la misma página gracias a la `Referrer-Policy:
 * strict-origin-when-cross-origin` fijada en `SecurityHeaders`).
 *
 * Se compara contra el host de la propia petición (`$request->getHost()`), no
 * contra un dominio fijo en config: el widget siempre pide a `url('/')`, que
 * es el host que sirvió la página, sea cual sea (local, staging, producción).
 * Comparar contra la propia petición vale en cualquier entorno sin mantener
 * una lista de dominios ni depender de que `APP_URL` esté bien puesto.
 *
 * No es infalible: cualquiera puede falsificar `Origin`/`Referer` con curl o
 * un script. Pero para el ataque real que motiva esto —copiar la URL del
 * devtools y abrirla en otra pestaña, o que otra web reutilice el JSON con un
 * `<script>`/`fetch` simple— ninguno de los dos manda una cabecera que
 * cuadre, así que se queda fuera.
 */
class EnsureRequestIsSameOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        $host = $origin !== null ? parse_url($origin, PHP_URL_HOST) : null;

        if ($host === null || strcasecmp($host, $request->getHost()) !== 0) {
            abort(403, 'Este recurso solo se sirve desde su propia página.');
        }

        return $next($request);
    }
}
