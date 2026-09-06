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
    private function peticion(array $headers = [], string $remoto = '203.0.113.9'): Request
    {
        $server = ['REMOTE_ADDR' => $remoto];

        foreach ($headers as $nombre => $valor) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($nombre))] = $valor;
        }

        return Request::create('/', 'POST', [], [], [], $server);
    }

    #[Test]
    public function cloudflare_manda_la_ip_del_visitante_en_su_cabecera(): void
    {
        $r = $this->peticion(['CF-Connecting-IP' => '198.51.100.7'], remoto: '172.68.1.1');

        $this->assertSame('198.51.100.7', ClientIp::public($r));
    }

    #[Test]
    public function cloudflare_tiene_prioridad_sobre_el_resto(): void
    {
        $r = $this->peticion([
            'X-Forwarded-For' => '10.0.0.1, 192.0.2.50',
            'CF-Connecting-IP' => '198.51.100.7',
        ], remoto: '172.68.1.1');

        $this->assertSame('198.51.100.7', ClientIp::public($r));
    }

    #[Test]
    public function de_x_forwarded_for_se_coge_la_primera_publica(): void
    {
        // «cliente, proxy1, proxy2»: el primero es quien originó la petición.
        $r = $this->peticion(['X-Forwarded-For' => '198.51.100.7, 10.0.0.1, 172.16.0.2']);

        $this->assertSame('198.51.100.7', ClientIp::public($r));
    }

    #[Test]
    public function una_cabecera_con_ip_privada_se_ignora(): void
    {
        // Una cabecera que trae 192.168.x no dice de dónde viene la petición,
        // dice por dónde ha pasado.
        $r = $this->peticion(['X-Forwarded-For' => '192.168.1.50'], remoto: '198.51.100.7');

        $this->assertSame('198.51.100.7', ClientIp::public($r));
    }

    #[Test]
    public function sin_cabeceras_vale_la_ip_de_la_conexion_si_es_publica(): void
    {
        $this->assertSame('203.0.113.9', ClientIp::public($this->peticion()));
    }

    #[Test]
    public function en_local_no_hay_ip_publica_y_devuelve_null(): void
    {
        // Guardar 127.0.0.1 en `ip_public` sería mentir en la columna.
        $this->assertNull(ClientIp::public($this->peticion(remoto: '127.0.0.1')));
        $this->assertNull(ClientIp::public($this->peticion(remoto: '192.168.1.20')));
    }

    #[Test]
    public function una_cabecera_con_basura_no_rompe_nada(): void
    {
        $r = $this->peticion(['CF-Connecting-IP' => 'no-soy-una-ip'], remoto: '203.0.113.9');

        $this->assertSame('203.0.113.9', ClientIp::public($r));
    }

    #[Test]
    public function la_ip_de_conexion_no_mira_cabeceras(): void
    {
        // Para el rate limit hace falta la que no se puede falsear desde fuera.
        $r = $this->peticion(['CF-Connecting-IP' => '198.51.100.7'], remoto: '172.68.1.1');

        $this->assertSame('172.68.1.1', ClientIp::connection($r));
    }
}
