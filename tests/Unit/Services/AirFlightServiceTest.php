<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Http\Resources\V2\AirFlight\AirFlightResource;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Services\AirFlight\AirFlightService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `getActiveAircrafts()` alimenta el mapa en vivo. Filtraba por
 * `seen_last_at` del avión, que se actualiza con cualquier mensaje —también
 * uno sin posición (un squawk suelto)—. Un avión sin ninguna ruta reciente
 * con lat/lon seguía "visto" y se colaba en el mapa sin coordenadas: el
 * frontend acababa dibujándolo en un punto inventado (ver
 * planeObject.js::updateData).
 */
class AirFlightServiceTest extends TestCase
{
    use RefreshDatabase;

    private AirFlightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AirFlightService::class);
    }

    #[Test]
    public function un_avion_sin_ninguna_ruta_no_sale_como_activo(): void
    {
        AirFlightAirPlane::create(['icao' => 'SINRUTA', 'seen_last_at' => Carbon::now()]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function un_avion_con_ping_reciente_pero_sin_posicion_no_sale_como_activo(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'SOLOSQUAWK', 'seen_last_at' => Carbon::now()]);

        // Mensaje recibido hace un instante, pero sin lat/lon: un squawk o
        // una altitud sueltos, sin posición decodificada.
        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'squawk' => '7000',
            'seen_at' => Carbon::now()->subSeconds(30),
        ]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function un_avion_con_posicion_antigua_fuera_de_ventana_no_sale_como_activo(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'VIEJO', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function un_avion_con_posicion_reciente_si_sale_como_activo(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'ACTIVO', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(2),
        ]);

        $activos = $this->service->getActiveAircrafts(10);

        $this->assertCount(1, $activos);
        $this->assertSame('ACTIVO', $activos->first()->icao);
        $this->assertNotNull($activos->first()->latestRoute);
        $this->assertSame(36.71, (float) $activos->first()->latestRoute->lat);
    }

    /**
     * Reproduce el caso real reportado: un avión con una posición reciente
     * de verdad, seguido de un mensaje posterior sin posición (un squawk
     * suelto). `latestRoute` pasa a ser ese último mensaje sin lat/lon, así
     * que el JSON que consume el mapa mandaba `lat`/`lon` a `null` aunque el
     * avión sí tuviera una posición reciente — y el frontend lo dibujaba en
     * (0, 0).
     */
    #[Test]
    public function un_mensaje_sin_posicion_posterior_no_borra_la_ultima_posicion_real(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'CONSQUAWK', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(5),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'squawk' => '7000',
            'seen_at' => Carbon::now()->subMinute(),
        ]);

        $activos = $this->service->getActiveAircrafts(10);
        $this->assertCount(1, $activos);

        $json = AirFlightResource::collection($activos)->resolve();

        $this->assertSame(36.71, $json[0]['lat']);
        $this->assertSame(-6.41, $json[0]['lon']);
        // El squawk sí viene del mensaje más reciente.
        $this->assertSame('7000', $json[0]['squawk']);
    }
}
