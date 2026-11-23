<?php

declare(strict_types=1);

namespace App\Services\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
 *  - sólo `http` y `https`, nada de `file://`, `gopher://` ni `dict://`, y
 *    sólo por sus puertos (80 y 443): con cualquier otro, el servidor serviría
 *    para averiguar qué puertos tiene abiertos otra máquina;
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
        } catch (Throwable $e) {
            Log::warning('PublicUrlFetcher: request failed', [
                'host' => $target['host'],
                'error' => $e->getMessage(),
            ]);

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
     * Rangos IPv4 e IPv6 no enrutables públicamente que `FILTER_FLAG_NO_PRIV_RANGE`
     * / `FILTER_FLAG_NO_RES_RANGE` no descartan por defecto (CGNAT, multicast, NAT64…).
     *
     * @var list<string>
     */
    private const BLOCKED_CIDRS = [
        '100.64.0.0/10',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '198.18.0.0/15',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '::/128',
        '64:ff9b::/96',
        'ff00::/8',
    ];

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

        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $rawHost = (string) ($parts['host'] ?? '');
        $host = trim($rawHost, '[]');
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if ($host === '' || ! in_array($port, [80, 443], true)) {
            return null;
        }

        $ips = $this->resolve($host);

        if ($ips === []) {
            return null;
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return null;
            }
        }

        return [
            'host' => $host,
            'port' => $port,
            // libcurl `CURLOPT_RESOLVE` espera `HOST:PORT:ADDRESS` sin corchetes.
            'ip' => trim($ips[0], '[]'),
        ];
    }

    /**
     * Comprueba que la IP es estrictamente enrutable en Internet público.
     */
    private function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evalúa pertenencia de una IPv4 o IPv6 a un prefijo CIDR mediante máscara binaria.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $prefixLength] = explode('/', $cidr, 2);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $prefix = (int) $prefixLength;
        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }

    /**
     * Las IPs a las que apunta un host (IPv4 e IPv6).
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

        $ips = [];
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if (is_string($ip) && $ip !== '') {
                    $ips[] = $ip;
                }
            }
        }

        if ($ips === []) {
            $ipv4 = @gethostbynamel($host);
            if (is_array($ipv4)) {
                $ips = $ipv4;
            }
        }

        return array_values(array_unique($ips));
    }
}
