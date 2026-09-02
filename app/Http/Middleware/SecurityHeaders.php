<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas.
 *
 * Van aquí y no en el virtualhost a propósito (auditoría AR-D01). Había cuatro
 * configuraciones de servidor con contenidos distintos —dos de ellas sin
 * ninguna cabecera— y la más fácil de copiar por estar en la raíz del
 * repositorio era justo una de las que no las tenía. Con el panel de Filament
 * detrás, eso significa un panel de administración **clickjackeable**.
 *
 * Poniéndolas en la aplicación viajan con el código: se despliegan solas, valen
 * igual con Apache, con nginx o con Docker, y no dependen de que alguien
 * copiara el `.conf` correcto. Los virtualhosts de `docs/deploys/` las repiten
 * —repetir una cabecera no hace daño— para que un servidor bien configurado las
 * mande aunque PHP no llegue a ejecutarse.
 */
class SecurityHeaders
{
    /**
     * Cabeceras fijas, las mismas para toda respuesta.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        // Sin esto, el navegador puede adivinar el tipo de un fichero servido
        // por /file/get y tratarlo como HTML. Con un PDF entre los tipos
        // admitidos, es la diferencia entre servir un fichero y ejecutarlo.
        'X-Content-Type-Options' => 'nosniff',

        // Clickjacking sobre /admin y /panel. `frame-ancestors` de la CSP es lo
        // moderno, pero esta cabecera la siguen entendiendo navegadores que
        // aquella no.
        'X-Frame-Options' => 'SAMEORIGIN',

        // Que un enlace saliente no se lleve la ruta completa —con sus tokens
        // de newsletter o de CV compartido— en el `Referer`.
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Nada de esto lo usa la plataforma. Se apaga para que tampoco lo use
        // lo que se cuele dentro.
        'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $nombre => $valor) {
            // `headers->set()` sin sobrescribir lo que una respuesta concreta
            // haya decidido: `CurriculumController::shared()` pone su propio
            // `X-Robots-Tag` y no queremos pisar ese tipo de decisiones.
            if (! $response->headers->has($nombre)) {
                $response->headers->set($nombre, $valor);
            }
        }

        // HSTS sólo sobre HTTPS. Mandarla por HTTP no sirve de nada —el
        // navegador la ignora— y en desarrollo (http://localhost) es peor que
        // inútil: deja el dominio marcado como «sólo HTTPS» en el navegador
        // durante un año.
        //
        // Sin `preload` ni `includeSubDomains` a propósito: `preload` es una
        // lista de la que se sale con meses de trámite, y los subdominios
        // (ws.raupulus.dev y los que vengan) no son cosa de esta aplicación.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // El servidor no tiene por qué anunciar con qué está hecho. nginx y
        // Apache tienen lo suyo (`server_tokens`, `ServerTokens`); esto es lo
        // que añade PHP.
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
