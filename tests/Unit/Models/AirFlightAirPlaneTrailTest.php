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

    private function createRoute(AirFlightAirPlane $airplane, float $lat, float $lon, Carbon $seenAt): AirFlightRoute
    {
        return AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'flight' => 'TEST123',
            'lat' => $lat,
            'lon' => $lon,
            'seen_at' => $seenAt,
        ]);
    }

    #[Test]
    public function does_not_join_todays_trail_with_a_flyover_from_days_ago(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'ABC123']);

        $old = $this->createRoute($airplane, AirFlightAirPlane::RECEIVER_LAT, AirFlightAirPlane::RECEIVER_LON, Carbon::now()->subDays(3));
        $recent = $this->createRoute($airplane, 36.71, -6.41, Carbon::now()->subMinutes(5));

        $trail = $airplane->trail()->get();

        $this->assertTrue($trail->contains('id', $recent->id));
        $this->assertFalse($trail->contains('id', $old->id));
    }

    #[Test]
    public function discards_a_reading_outside_the_receivers_plausible_range(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'DEF456']);

        // Punto en África central: un fallo de decodificación, no un avión
        // real dentro del alcance del receptor de Chipiona.
        $glitch = $this->createRoute($airplane, 2.0, 10.0, Carbon::now()->subMinutes(2));
        $real = $this->createRoute($airplane, 36.71, -6.41, Carbon::now()->subMinute());

        $trail = $airplane->trail()->get();

        $this->assertTrue($trail->contains('id', $real->id));
        $this->assertFalse($trail->contains('id', $glitch->id));
    }

    #[Test]
    public function keeps_the_normal_trail_of_a_recent_nearby_pass(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'GHI789']);

        $p1 = $this->createRoute($airplane, 36.70, -6.45, Carbon::now()->subMinutes(3));
        $p2 = $this->createRoute($airplane, 36.72, -6.42, Carbon::now()->subMinutes(2));
        $p3 = $this->createRoute($airplane, 36.74, -6.40, Carbon::now()->subMinute());

        $trail = $airplane->trail()->get();

        $this->assertSame([$p1->id, $p2->id, $p3->id], $trail->pluck('id')->all());
    }
}
