<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\WeatherStation\AEMET\AEMETStationObservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `aemet:station-observations`.
 *
 * Los tres fixtures son reales, capturados en directo el 2026-09-16 (ver
 * docs/future/archived/revisar-aemet.md): Chipiona y Almonte reportan viento,
 * Rota (idema `5910X`, no `5910` — ese es el del inventario climatológico, un
 * producto distinto) no reporta viento en ninguno de sus registros.
 */
class AemetStationObservationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CHIPIONA_BODY = <<<'JSON'
        [
            {
                "idema": "5906X",
                "ubi": "CHIPIONA  ECA",
                "lat": 36.75,
                "lon": -6.400558,
                "alt": 10.0,
                "fint": "2026-09-16T10:00:00+0000",
                "ta": 23.0,
                "tamin": 22.4,
                "tamax": 23.0,
                "hr": 74.0,
                "prec": 0.0,
                "vv": 4.7,
                "vmax": 7.4,
                "dv": 271.0,
                "dmax": 270.0
            }
        ]
        JSON;

    private const ROTA_BODY = <<<'JSON'
        [
            {
                "idema": "5910X",
                "ubi": "ROTA BASE NAVAL",
                "lat": 36.638879,
                "lon": -6.332591,
                "alt": 21.0,
                "fint": "2026-09-16T11:00:00+0000",
                "ta": 24.0,
                "tamin": 23.1,
                "tamax": 24.3,
                "hr": 71.0,
                "prec": 0.0
            }
        ]
        JSON;

    private const ALMONTE_BODY = <<<'JSON'
        [
            {
                "idema": "5858X",
                "ubi": "ALMONTE  DOÑANA ",
                "lat": 36.988553,
                "lon": -6.443024,
                "alt": 5.0,
                "fint": "2026-09-16T11:00:00+0000",
                "ta": 27.3,
                "tamin": 26.9,
                "tamax": 27.6,
                "hr": 57.0,
                "prec": 0.0,
                "vv": 6.0,
                "vmax": 8.2,
                "dv": 247.0,
                "dmax": 250.0
            }
        ]
        JSON;

    /**
     * `Http::fake()` no compone llamadas por URL entre sí como los otros
     * tests de este archivo: aquí hacen falta las tres estaciones a la vez,
     * así que se registran juntas en un único `Http::fake()`.
     */
    private function fakeAllThreeStations(): void
    {
        Http::fake([
            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5906X' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/fake-5906X']),
                200
            ),
            'https://opendata.aemet.es/opendata/sh/fake-5906X' => Http::response(self::CHIPIONA_BODY, 200),

            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5910X' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/fake-5910X']),
                200
            ),
            'https://opendata.aemet.es/opendata/sh/fake-5910X' => Http::response(self::ROTA_BODY, 200),

            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5858X' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/fake-5858X']),
                200
            ),
            'https://opendata.aemet.es/opendata/sh/fake-5858X' => Http::response(self::ALMONTE_BODY, 200),
        ]);
    }

    #[Test]
    public function it_saves_one_row_per_configured_station_tagged_with_its_zone(): void
    {
        $this->fakeAllThreeStations();

        $this->artisan('aemet:station-observations')->assertExitCode(0);

        $this->assertSame(3, AEMETStationObservation::count());

        $this->assertDatabaseHas('meteorology_aemet_station_observations', [
            'station_zone' => 'chipiona_eca',
            'station_id' => '5906X',
            'temperature' => 23.0,
            'wind_speed' => 4.7,
        ]);

        $this->assertDatabaseHas('meteorology_aemet_station_observations', [
            'station_zone' => 'almonte',
            'station_id' => '5858X',
            'temperature' => 27.3,
            'wind_speed' => 6.0,
        ]);
    }

    /**
     * Rota no reporta viento — las columnas tienen que quedar NULL, no 0 ni
     * ausentes.
     */
    #[Test]
    public function a_station_without_wind_data_stores_null_not_zero(): void
    {
        $this->fakeAllThreeStations();

        $this->artisan('aemet:station-observations');

        $rota = AEMETStationObservation::where('station_zone', 'rota_base_naval')->first();

        $this->assertNotNull($rota);
        $this->assertSame(24.0, $rota->temperature);
        $this->assertNull($rota->wind_speed);
        $this->assertNull($rota->wind_gust);
        $this->assertNull($rota->wind_direction);
        $this->assertNull($rota->wind_gust_direction);
    }

    #[Test]
    public function running_it_twice_updates_instead_of_duplicating(): void
    {
        $this->fakeAllThreeStations();

        $this->artisan('aemet:station-observations')->assertExitCode(0);
        $this->artisan('aemet:station-observations')->assertExitCode(0);

        $this->assertSame(3, AEMETStationObservation::count());
    }

    /**
     * Reproduce el caso real de Rota antes de corregir el `idema`: un 404
     * envuelto en 200 para una estación no debe impedir que las otras dos se
     * guarden.
     */
    #[Test]
    public function one_station_failing_does_not_block_the_other_two(): void
    {
        Http::fake([
            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5906X' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/fake-5906X']),
                200
            ),
            'https://opendata.aemet.es/opendata/sh/fake-5906X' => Http::response(self::CHIPIONA_BODY, 200),

            // idema equivocado: 200 con estado 404 dentro, como pasaba con "5910" sin la X.
            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5910X' => Http::response(
                json_encode(['descripcion' => 'No hay datos que satisfagan esos criterios', 'estado' => 404]),
                200
            ),

            'https://opendata.aemet.es/opendata/api/observacion/convencional/datos/estacion/5858X' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => 'https://opendata.aemet.es/opendata/sh/fake-5858X']),
                200
            ),
            'https://opendata.aemet.es/opendata/sh/fake-5858X' => Http::response(self::ALMONTE_BODY, 200),
        ]);

        $this->artisan('aemet:station-observations')->assertExitCode(0);

        $this->assertSame(2, AEMETStationObservation::count());
        $this->assertDatabaseMissing('meteorology_aemet_station_observations', ['station_zone' => 'rota_base_naval']);
    }
}
