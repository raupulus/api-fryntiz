<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\WeatherStation\AEMET\AEMETUvi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `aemet:uvi`.
 *
 * El JSON de Cádiz (`id: "11012"`) es real, capturado en directo el
 * 2026-09-16 — ver docs/future/archived/revisar-aemet.md. El `id` de Chipiona
 * NO vale aquí: UVI solo cubre las 59 capitales de provincia.
 */
class AemetUviCommandTest extends TestCase
{
    use RefreshDatabase;

    private const UVI_BODY = <<<'JSON'
        {
            "FECHA_ELABORACION": "2026-09-16T03:52:01",
            "FECHA_MOD": "2026-09-15T12:00:00",
            "FECHA_VALIDEZ": "2026-09-16T12:00:00",
            "CIUDAD": [
                {"id": "02003", "valor": "Albacete", "uv": "8", "canarias": "0"},
                {"id": "11012", "valor": "Cádiz", "uv": "6", "canarias": "0"}
            ]
        }
        JSON;

    private function fakeUvi(string $body): void
    {
        $dataUrl = 'https://opendata.aemet.es/opendata/sh/fake-uvi';

        Http::fake([
            'https://opendata.aemet.es/opendata/api/prediccion/especifica/uvi/0' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => $dataUrl]),
                200,
                ['Content-Type' => 'application/json']
            ),
            $dataUrl => Http::response($body, 200, ['Content-Type' => 'application/json;charset=ISO-8859-15']),
        ]);
    }

    #[Test]
    public function it_saves_the_uv_index_of_the_configured_city(): void
    {
        config(['aemet.uvi_city_code' => '11012']);
        $this->fakeUvi(self::UVI_BODY);

        $this->artisan('aemet:uvi')->assertExitCode(0);

        $this->assertSame(1, AEMETUvi::count());

        $row = AEMETUvi::first();
        $this->assertSame(6, $row->uv_index);
        $this->assertSame('2026-09-16', $row->valid_date->toDateString());
    }

    #[Test]
    public function running_it_twice_for_the_same_day_updates_instead_of_duplicating(): void
    {
        config(['aemet.uvi_city_code' => '11012']);
        $this->fakeUvi(self::UVI_BODY);

        $this->artisan('aemet:uvi')->assertExitCode(0);
        $this->artisan('aemet:uvi')->assertExitCode(0);

        $this->assertSame(1, AEMETUvi::count());
    }

    #[Test]
    public function a_city_that_is_not_in_the_response_does_not_crash_the_command(): void
    {
        config(['aemet.uvi_city_code' => '99999']);
        $this->fakeUvi(self::UVI_BODY);

        $this->artisan('aemet:uvi')->assertExitCode(0);

        $this->assertSame(0, AEMETUvi::count());
    }
}
