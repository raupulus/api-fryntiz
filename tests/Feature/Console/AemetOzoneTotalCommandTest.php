<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\WeatherStation\AEMET\AEMETOzoneTotal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `aemet:ozone-total`.
 *
 * Existe porque `aemet:ozone` (ahora `aemet:ozone-profile`) llevaba desde
 * siempre pidiendo el perfil vertical de una ozonosonda, no el ozono total de
 * superficie que su nombre prometía — ver docs/future/archived/revisar-aemet.md. Este
 * comando es el producto que faltaba de verdad, verificado contra la
 * respuesta real de AEMET del 2026-09-14 (CSV en UTF-8 con dos líneas de
 * cabecera antes de la tabla).
 */
class AemetOzoneTotalCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CSV_BODY = <<<'CSV'
        "CAPA DE OZONO"
        "13-09-26"
        "Estación";"Indicativo";"OZONO"
        "A Coruña";"1387";"289"
        "Izaña";"C430E";"284"
        "Madrid, Ciudad Universitaria";"3194U";"303"
        CSV;

    #[Test]
    public function it_parses_and_persists_a_row_per_station(): void
    {
        $this->fakeAemetOzoneTotal(self::CSV_BODY);

        $this->artisan('aemet:ozone-total')->assertExitCode(0);

        $this->assertSame(3, AEMETOzoneTotal::count());

        $coruna = AEMETOzoneTotal::where('station_code', '1387')->first();
        $this->assertNotNull($coruna);
        $this->assertSame('A Coruña', $coruna->station_name);
        $this->assertSame(289, $coruna->ozone_value);
        $this->assertSame('2026-09-13', $coruna->measured_on->toDateString());
    }

    #[Test]
    public function running_it_twice_updates_instead_of_duplicating(): void
    {
        $this->fakeAemetOzoneTotal(self::CSV_BODY);

        $this->artisan('aemet:ozone-total')->assertExitCode(0);
        $this->artisan('aemet:ozone-total')->assertExitCode(0);

        $this->assertSame(3, AEMETOzoneTotal::count());
    }

    #[Test]
    public function an_empty_body_does_not_crash_the_command(): void
    {
        $this->fakeAemetOzoneTotal('');

        $this->artisan('aemet:ozone-total')->assertExitCode(0);

        $this->assertSame(0, AEMETOzoneTotal::count());
    }

    private function fakeAemetOzoneTotal(string $csvBody): void
    {
        config(['aemet.api_key' => 'test-key']);

        $dataUrl = 'https://opendata.aemet.es/opendata/sh/fake-ozono-total';

        Http::fake([
            'https://opendata.aemet.es/opendata/api/red/especial/ozono' => Http::response(
                json_encode(['descripcion' => 'exito', 'estado' => 200, 'datos' => $dataUrl]),
                200,
                ['Content-Type' => 'application/json']
            ),
            $dataUrl => Http::response($csvBody, 200, ['Content-Type' => 'text/plain;charset=UTF-8']),
        ]);
    }
}
