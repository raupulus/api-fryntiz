<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `trail()` traza la línea de vuelo del mapa uniendo puntos con una
 * `LineString`. Sin límite de tiempo ni de alcance, un ICAO visto en dos
 * sobrevuelos de días distintos —o con una sola lectura mal decodificada—
 * quedaba unido por una línea recta como si fuera un único vuelo continuo:
 * el origen de la traza no correspondía a ninguna posición real de esa
 * pasada.
 */
class AirFlightAirPlaneTrailTest extends TestCase
{
    use RefreshDatabase;

    private function crearRuta(AirFlightAirPlane $avion, float $lat, float $lon, Carbon $seenAt): AirFlightRoute
    {
        return AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'flight' => 'TEST123',
            'lat' => $lat,
            'lon' => $lon,
            'seen_at' => $seenAt,
        ]);
    }

    #[Test]
    public function no_une_la_traza_de_hoy_con_un_sobrevuelo_de_hace_dias(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'ABC123']);

        $antigua = $this->crearRuta($avion, AirFlightAirPlane::RECEIVER_LAT, AirFlightAirPlane::RECEIVER_LON, Carbon::now()->subDays(3));
        $reciente = $this->crearRuta($avion, 36.71, -6.41, Carbon::now()->subMinutes(5));

        $traza = $avion->trail()->get();

        $this->assertTrue($traza->contains('id', $reciente->id));
        $this->assertFalse($traza->contains('id', $antigua->id));
    }

    #[Test]
    public function descarta_una_lectura_fuera_del_alcance_plausible_del_receptor(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'DEF456']);

        // Punto en África central: un fallo de decodificación, no un avión
        // real dentro del alcance del receptor de Chipiona.
        $glitch = $this->crearRuta($avion, 2.0, 10.0, Carbon::now()->subMinutes(2));
        $real = $this->crearRuta($avion, 36.71, -6.41, Carbon::now()->subMinute());

        $traza = $avion->trail()->get();

        $this->assertTrue($traza->contains('id', $real->id));
        $this->assertFalse($traza->contains('id', $glitch->id));
    }

    #[Test]
    public function conserva_la_traza_normal_de_una_pasada_reciente_y_cercana(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'GHI789']);

        $p1 = $this->crearRuta($avion, 36.70, -6.45, Carbon::now()->subMinutes(3));
        $p2 = $this->crearRuta($avion, 36.72, -6.42, Carbon::now()->subMinutes(2));
        $p3 = $this->crearRuta($avion, 36.74, -6.40, Carbon::now()->subMinute());

        $traza = $avion->trail()->get();

        $this->assertSame([$p1->id, $p2->id, $p3->id], $traza->pluck('id')->all());
    }
}
