<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\WeatherStation\AEMETService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `getContamination()`, `getOzone()` y `getSunRadiation()` de `AEMETService`.
 *
 * No los llama nadie en producción —los comandos reales usan `AEMETHelper`,
 * con sus propias rutas—, pero hasta el 2026-09-14 dos de las tres pedían
 * rutas que la documentación de AEMET marca como incorrectas (404:
 * `contaminacionfondo` sin estación, `radiacionsolar` en vez de `radiacion`),
 * y las tres asumían un cuerpo JSON cuando los tres productos son texto/CSV.
 * Cualquiera que retome la migración a este servicio heredaba ambos fallos
 * sin verificarlo de nuevo. Ver docs/future/revisar-aemet.md.
 */
class AEMETServiceTest extends TestCase
{
    #[Test]
    public function get_ozone_requests_the_correct_endpoint_and_returns_the_raw_csv(): void
    {
        $this->fakeEnvelope('/red/especial/ozono', "\"CAPA DE OZONO\"\n\"13-09-26\"\n");

        $body = app(AEMETService::class)->getOzone();

        $this->assertIsString($body);
        $this->assertStringContainsString('CAPA DE OZONO', $body);

        Http::assertSent(fn ($request) => $request->url() === 'https://opendata.aemet.es/opendata/api/red/especial/ozono');
    }

    #[Test]
    public function get_sun_radiation_requests_radiacion_not_the_broken_radiacionsolar_route(): void
    {
        $this->fakeEnvelope('/red/especial/radiacion', '"RADIACION SOLAR"');

        $body = app(AEMETService::class)->getSunRadiation();

        $this->assertIsString($body);

        Http::assertSent(fn ($request) => $request->url() === 'https://opendata.aemet.es/opendata/api/red/especial/radiacion');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'radiacionsolar'));
    }

    #[Test]
    public function get_contamination_requests_the_configured_station_not_the_broken_stationless_route(): void
    {
        $this->fakeEnvelope('/red/especial/contaminacionfondo/estacion/17', '14-09-2026 00:10 SO2(001): +00003.54');

        $body = app(AEMETService::class)->getContamination();

        $this->assertIsString($body);

        Http::assertSent(fn ($request) => $request->url() === 'https://opendata.aemet.es/opendata/api/red/especial/contaminacionfondo/estacion/17');
    }

    private function fakeEnvelope(string $endpoint, string $body): void
    {
        config(['aemet.api_key' => 'test-key']);

        $dataUrl = 'https://opendata.aemet.es/opendata/sh/fake-data';

        Http::fake([
            "https://opendata.aemet.es/opendata/api{$endpoint}" => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => $dataUrl]),
                200,
                ['Content-Type' => 'application/json']
            ),
            $dataUrl => Http::response($body, 200, ['Content-Type' => 'text/plain;charset=UTF-8']),
        ]);
    }
}
