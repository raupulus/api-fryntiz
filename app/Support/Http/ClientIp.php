<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;

use function explode;
use function filter_var;
use function is_string;
use function request;
use function trim;

/**
 * IP real de quien hace la petición, con o sin proxy delante.
 *
 * ## Por qué no se resuelve con `TRUSTED_PROXIES`
 *
 * El camino «oficial» de Laravel es declarar los rangos del proxy en
 * `TRUSTED_PROXIES` para que `$request->ip()` desenvuelva `X-Forwarded-For`.
 * Con Cloudflare delante eso obliga a mantener a mano una lista de rangos
 * públicos que Cloudflare cambia cuando quiere: el día que añaden un rango, la
 * API empieza a ver a todo el mundo con la misma IP y no se entera nadie.
 *
 * Aquí se lee directamente la cabecera que el propio proxy escribe, que es la
 * que lleva la IP de origen ya resuelta:
 *
 *  - `CF-Connecting-IP` — la pone Cloudflare en todas las peticiones y contiene
 *    **una sola** IP, la del visitante. No hay lista que mantener.
 *  - `X-Forwarded-For` — el estándar de cualquier otro proxy o nginx. Puede
 *    traer varias separadas por comas; la primera es el cliente original.
 *  - `X-Real-IP` — la que suele poner nginx cuando se configura a mano.
 *
 * Y se descartan las privadas y reservadas (`FILTER_FLAG_NO_PRIV_RANGE` y
 * `NO_RES_RANGE`): una cabecera que trae `192.168.1.50` no está diciendo de
 * dónde viene la petición, está diciendo por dónde ha pasado.
 *
 * ## Lo que hay que saber al usarla
 *
 * Estas cabeceras las escribe el proxy, y quien llegue al servidor sin pasar
 * por él puede escribirlas también. Por eso {@see self::public()} sirve para
 * **anotar** de dónde viene un dispositivo, que es su uso aquí, y no para
 * decidir permisos. Para el rate limit sigue usándose `$request->ip()`, que no
 * se puede falsear desde fuera.
 */
final class ClientIp
{
    /**
     * Cabeceras que puede escribir un proxy, en orden de fiabilidad.
     *
     * @var list<string>
     */
    private const HEADERS = [
        'CF-Connecting-IP',
        'True-Client-IP',
        'X-Forwarded-For',
        'X-Real-IP',
    ];

    /**
     * IP pública de origen, o `null` si no se puede determinar ninguna.
     *
     * Devuelve `null` —y no la IP de la conexión— a propósito: en desarrollo, o
     * detrás de una NAT sin proxy, lo único que hay es una IP privada, y
     * guardarla en `ip_public` sería mentir en la columna.
     */
    public static function public(?Request $request = null): ?string
    {
        $request ??= request();

        foreach (self::HEADERS as $header) {
            $valor = $request->header($header);

            if (! is_string($valor) || $valor === '') {
                continue;
            }

            // `X-Forwarded-For` encadena proxies: «cliente, proxy1, proxy2».
            // El primero es el que originó la petición.
            foreach (explode(',', $valor) as $candidata) {
                $ip = self::publicaONull(trim($candidata));

                if ($ip !== null) {
                    return $ip;
                }
            }
        }

        // Sin proxy delante, la IP de la conexión ya es la de origen — pero
        // sólo vale si es pública.
        return self::publicaONull((string) $request->ip());
    }

    /**
     * IP de la conexión tal cual, sin mirar cabeceras.
     *
     * Es la que debe usarse para limitar por IP: no se puede falsificar desde
     * fuera, mientras que cualquiera puede mandar una cabecera inventada.
     */
    public static function connection(?Request $request = null): ?string
    {
        return ($request ?? request())->ip();
    }

    /**
     * La IP si es pública y válida; `null` en cualquier otro caso.
     */
    private static function publicaONull(string $ip): ?string
    {
        if ($ip === '') {
            return null;
        }

        $valida = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $valida === false ? null : $ip;
    }
}
