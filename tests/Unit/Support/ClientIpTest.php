<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Http\ClientIp;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La IP de origen se saca de la cabecera que escribe el proxy.
 *
 * El camino «oficial» de Laravel —declarar los rangos del proxy en
 * `TRUSTED_PROXIES` para que `$request->ip()` desenvuelva `X-Forwarded-For`—
 * obliga a mantener a mano la lista de rangos públicos de Cloudflare, que
 * cambian sin avisar: el día que añaden uno, la API ve a todo el mundo con la
 * misma IP y no salta nada. Leyendo la cabecera no hay lista que mantener.
 */
class ClientIpTest extends TestCase
{
    private function makeRequest(array $headers = [], string $remoteAddress = '203.0.113.9'): Request
    {
        $server = ['REMOTE_ADDR' => $remoteAddress];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($name))] = $value;
        }

        return Request::create('/', 'POST', [], [], [], $server);
    }

    #[Test]
    public function cloudflare_sends_the_visitor_ip_in_its_header(): void
    {
        $request = $this->makeRequest(['CF-Connecting-IP' => '198.51.100.7'], remoteAddress: '172.68.1.1');

        $this->assertSame('198.51.100.7', ClientIp::public($request));
    }

    #[Test]
    public function cloudflare_takes_priority_over_the_rest(): void
    {
        $request = $this->makeRequest([
            'X-Forwarded-For' => '10.0.0.1, 192.0.2.50',
            'CF-Connecting-IP' => '198.51.100.7',
        ], remoteAddress: '172.68.1.1');

        $this->assertSame('198.51.100.7', ClientIp::public($request));
    }

    #[Test]
    public function the_first_public_ip_is_taken_from_x_forwarded_for(): void
    {
        // «cliente, proxy1, proxy2»: el primero es quien originó la petición.
        $request = $this->makeRequest(['X-Forwarded-For' => '198.51.100.7, 10.0.0.1, 172.16.0.2']);

        $this->assertSame('198.51.100.7', ClientIp::public($request));
    }

    #[Test]
    public function a_header_with_a_private_ip_is_ignored(): void
    {
        // Una cabecera que trae 192.168.x no dice de dónde viene la petición,
        // dice por dónde ha pasado.
        $request = $this->makeRequest(['X-Forwarded-For' => '192.168.1.50'], remoteAddress: '198.51.100.7');

        $this->assertSame('198.51.100.7', ClientIp::public($request));
    }

    #[Test]
    public function without_headers_the_connection_ip_is_used_if_public(): void
    {
        $this->assertSame('203.0.113.9', ClientIp::public($this->makeRequest()));
    }

    #[Test]
    public function locally_there_is_no_public_ip_and_it_returns_null(): void
    {
        // Guardar 127.0.0.1 en `ip_public` sería mentir en la columna.
        $this->assertNull(ClientIp::public($this->makeRequest(remoteAddress: '127.0.0.1')));
        $this->assertNull(ClientIp::public($this->makeRequest(remoteAddress: '192.168.1.20')));
    }

    #[Test]
    public function a_header_with_garbage_does_not_break_anything(): void
    {
        $request = $this->makeRequest(['CF-Connecting-IP' => 'no-soy-una-ip'], remoteAddress: '203.0.113.9');

        $this->assertSame('203.0.113.9', ClientIp::public($request));
    }

    #[Test]
    public function the_connection_ip_ignores_headers(): void
    {
        // Para el rate limit hace falta la que no se puede falsear desde fuera.
        $request = $this->makeRequest(['CF-Connecting-IP' => '198.51.100.7'], remoteAddress: '172.68.1.1');

        $this->assertSame('172.68.1.1', ClientIp::connection($request));
    }
}
