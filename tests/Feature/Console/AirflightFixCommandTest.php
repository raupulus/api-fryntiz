<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `airflight:fix` rellena `country`/`flag` de los aviones que les falte,
 * buscando su ICAO en `AirFlightAirPlane::FLAGS`.
 *
 * Su bucle paginaba con un total calculado una sola vez al principio y sin
 * excluir los ICAO que nunca se pueden resolver (rangos reservados): esas
 * filas se quedaban ocupando la misma página en cada vuelta y le robaban
 * recorrido a otras filas que sí eran corregibles, dejándolas sin tocar sin
 * avisar. Este test reproduce justo ese escenario: más de una página (100)
 * de ICAO irresolubles por delante de uno resoluble.
 */
class AirflightFixCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_fixes_a_resolvable_airplane_stuck_behind_more_than_one_page_of_unresolvable_ones(): void
    {
        // Rango 0x000000-0x003FFF: no cae en ningún país ni en los bloques
        // "Unassigned" de relleno de FLAGS, así que searchHex() siempre
        // devuelve null para estos.
        for ($i = 1; $i <= 120; $i++) {
            AirFlightAirPlane::create(['icao' => sprintf('0000%02x', $i)]);
        }

        // 0x348205 cae en el rango de España (0x340000-0x37FFFF).
        $spain = AirFlightAirPlane::create(['icao' => '348205']);

        $this->artisan('airflight:fix')->assertExitCode(0);

        $spain->refresh();
        $this->assertSame('Spain', $spain->country);
        $this->assertSame('Spain.png', $spain->flag);
    }

    #[Test]
    public function it_does_not_touch_airplanes_that_already_have_country_and_flag(): void
    {
        $airplane = AirFlightAirPlane::create([
            'icao' => '348205',
            'country' => 'Somewhere else',
            'flag' => 'custom.png',
        ]);

        $this->artisan('airflight:fix')->assertExitCode(0);

        $airplane->refresh();
        $this->assertSame('Somewhere else', $airplane->country);
        $this->assertSame('custom.png', $airplane->flag);
    }
}
