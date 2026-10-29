<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege la navegación de páginas profundas (`?page > 1`) y filtros de
 * historial en las vistas web públicas frente a scrappers externos, sin
 * afectar a la indexación ni al acceso directo a la página 1.
 *
 * Reglas:
 *  1. **Página 1 (sin `?page` o `?page=1` y sin filtros históricos):** se sirve
 *     libremente a cualquier visitante directo, marcador o rastreador SEO
 *     (Googlebot, Bingbot, etc.) aunque no envíe `Referer`.
 *  2. **Parámetro `page` malformado (no escalar, no numérico o `< 1`):** se
 *     rechaza de inmediato con `404 Not Found` antes de tocar la base de datos.
 *  3. **Paginación (`?page > 1`) o navegación histórica (`?month=`, `?year=`):**
 *     un usuario real siempre llega haciendo clic desde dentro de la propia
 *     web, por lo que el navegador envía `Referer` / `Origin` del mismo host o
 *     `Sec-Fetch-Site: same-origin`. Si no procede del propio dominio, se
 *     responde `403 Forbidden` sin ejecutar ninguna consulta SQL.
 *  4. **SEO (`X-Robots-Tag`):** en cualquier página con `?page > 1` se añade
 *     `X-Robots-Tag: noindex, nofollow` para que los buscadores consoliden la
 *     indexación en la página 1 y no agoten el presupuesto de rastreo.
 */
class EnsurePaginationIsSameOrigin
{
    public const ROBOTS_NOINDEX = 'noindex, nofollow';

    public function handle(Request $request, Closure $next): Response
    {
        $rawPage = $request->query('page');

        if ($rawPage !== null && $rawPage !== '') {
            if (! is_scalar($rawPage) || filter_var((string) $rawPage, FILTER_VALIDATE_INT) === false) {
                abort(404, 'Página fuera de rango.');
            }

            $page = (int) $rawPage;

            if ($page < 1) {
                abort(404, 'Página fuera de rango.');
            }
        } else {
            $page = 1;
        }

        if ($page > 1 && ! $this->isSameOriginRequest($request)) {
            abort(403, 'La paginación solo está disponible navegando desde la propia web.');
        }

        $response = $next($request);

        if ($page > 1 && ! $response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', self::ROBOTS_NOINDEX);
        }

        return $response;
    }

    /**
     * Comprueba si la petición procede del propio dominio mediante `Origin`,
     * `Referer` o `Sec-Fetch-Site: same-origin`.
     */
    private function isSameOriginRequest(Request $request): bool
    {
        $secFetchSite = $request->headers->get('Sec-Fetch-Site');

        if ($secFetchSite !== null && strcasecmp($secFetchSite, 'same-origin') === 0) {
            return true;
        }

        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');
        $host = $origin !== null ? parse_url($origin, PHP_URL_HOST) : null;

        return is_string($host) && strcasecmp($host, $request->getHost()) === 0;
    }
}
