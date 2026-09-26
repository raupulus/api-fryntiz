<?php

declare(strict_types=1);

namespace App\Services\Http;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pide una URL que elige quien escribe en el panel sin dejar que sirva para
 * leer la red interna (SSRF).
 *
 * Lo usan la tarjeta de enlace (título y descripción de una web) y la descarga
 * de una imagen pegada por URL en el editor. Las dos hacen una petición saliente
 * a una dirección ajena, y `http://169.254.169.254/` es el servicio de
 * metadatos de media nube y `http://127.0.0.1:9200` el Elasticsearch de al
 * lado. Por eso:
 *
 *  - sólo `http` y `https`, nada de `file://`, `gopher://` ni `dict://`;
 *  - el host se resuelve y ninguna de sus IPs puede ser privada, de bucle ni de
 *    enlace local (`interna.midominio.com` puede apuntar a 10.0.0.5);
 *  - la conexión va a la IP comprobada, no a lo que resuelva el DNS un momento
 *    después (si no, un DNS que cambia de respuesta se lo salta);
 *  - sin seguir redirecciones: una redirección es otra URL sin comprobar;
 *  - tiempo de espera corto y un tope de bytes.
 *
 * Ante la duda, null: no se pide nada.
 */
class PublicUrlFetcher
{
    /**
     * @param  array<string, string>  $headers
     */
    public function fetch(string $url, int $maxBytes, array $headers = [], int $timeout = 10): ?PublicUrlResponse
    {
        $target = $this->target($url);

        if ($target === null) {
            return null;
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(3)
                ->withHeaders($headers)
                ->withUserAgent('ApiRaupulusBot/1.0 (+https://api.raupulus.dev)')
                ->withoutRedirecting()
                ->withOptions([
                    'stream' => true,
                    'curl' => [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$target['ip']}"]],
                ])
                ->get($url);

            $stream = $response->toPsrResponse()->getBody();
            $body = '';

            while (! $stream->eof() && strlen($body) <= $maxBytes) {
                $body .= $stream->read(65_536);
            }
        } catch (Throwable) {
            return null;
        }

        $complete = strlen($body) <= $maxBytes;

        return new PublicUrlResponse(
            status: $response->status(),
            body: $complete ? $body : substr($body, 0, $maxBytes),
            contentType: (string) $response->header('Content-Type'),
            complete: $complete,
        );
    }

    /**
     * ¿Es una dirección pública a la que se puede ir?
     */
    public function isPublic(string $url): bool
    {
        return $this->target($url) !== null;
    }

    /**
     * Host, puerto e IP pública a la que conectar, o null si no se debe.
     *
     * @return array{host: string, port: int, ip: string}|null
     */
    private function target(string $url): ?array
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            return null;
        }

        $ips = $this->resolve(trim($host, '[]'));

        if ($ips === []) {
            return null;
        }

        foreach ($ips as $ip) {
            $public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

            if ($public === false) {
                return null;
            }
        }

        return [
            'host' => $host,
            'port' => (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80)),
            // curl quiere las IPv6 entre corchetes.
            'ip' => str_contains($ips[0], ':') ? '['.$ips[0].']' : $ips[0],
        ];
    }

    /**
     * Las IPs a las que apunta un host.
     *
     * Aparte para poder sustituirlo en los tests: si no, el caso feliz
     * dependería de que haya DNS y de que el dominio de prueba exista.
     *
     * @return list<string>
     */
    protected function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return gethostbynamel($host) ?: [];
    }
}
