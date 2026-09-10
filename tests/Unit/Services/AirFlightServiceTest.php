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
    public function an_airplane_without_any_route_does_not_show_as_active(): void
    {
        AirFlightAirPlane::create(['icao' => 'SINRUTA', 'seen_last_at' => Carbon::now()]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function an_airplane_with_a_recent_ping_but_no_position_does_not_show_as_active(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'SOLOSQUAWK', 'seen_last_at' => Carbon::now()]);

        // Mensaje recibido hace un instante, pero sin lat/lon: un squawk o
        // una altitud sueltos, sin posición decodificada.
        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'squawk' => '7000',
            'seen_at' => Carbon::now()->subSeconds(30),
        ]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function an_airplane_with_an_old_position_outside_the_window_does_not_show_as_active(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'VIEJO', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->assertCount(0, $this->service->getActiveAircrafts(10));
    }

    #[Test]
    public function an_airplane_with_a_recent_position_does_show_as_active(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'ACTIVO', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(2),
        ]);

        $activeAircrafts = $this->service->getActiveAircrafts(10);

        $this->assertCount(1, $activeAircrafts);
        $this->assertSame('ACTIVO', $activeAircrafts->first()->icao);
        $this->assertNotNull($activeAircrafts->first()->latestRoute);
        $this->assertSame(36.71, (float) $activeAircrafts->first()->latestRoute->lat);
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
    public function a_later_message_without_a_position_does_not_erase_the_last_real_position(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'CONSQUAWK', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'lat' => 36.71,
            'lon' => -6.41,
            'seen_at' => Carbon::now()->subMinutes(5),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'squawk' => '7000',
            'seen_at' => Carbon::now()->subMinute(),
        ]);

        $activeAircrafts = $this->service->getActiveAircrafts(10);
        $this->assertCount(1, $activeAircrafts);

        $json = AirFlightResource::collection($activeAircrafts)->resolve();

        $this->assertSame(36.71, $json[0]['lat']);
        $this->assertSame(-6.41, $json[0]['lon']);
        // El squawk sí viene del mensaje más reciente.
        $this->assertSame('7000', $json[0]['squawk']);
    }

    /**
     * El SDR sube `messages` cada vez que decodifica un mensaje Mode S
     * nuevo. Si dos sondeos del mismo avión traen el mismo contador dentro
     * de la última hora, es la misma detección re-decodificada, no una
     * nueva: se fusiona en la misma fila en vez de crear otra.
     */
    #[Test]
    public function two_probes_with_the_same_message_counter_merge_into_one_row(): void
    {
        $this->service->addAircraft([
            'icao' => 'MERGE01',
            'lat' => 36.71,
            'lon' => -6.41,
            'messages' => 100,
        ]);

        $this->service->addAircraft([
            'icao' => 'MERGE01',
            'squawk' => '7000',
            'messages' => 100,
        ]);

        $airplane = AirFlightAirPlane::where('icao', 'MERGE01')->firstOrFail();

        $this->assertSame(1, $airplane->routes()->count());

        $route = $airplane->routes()->first();
        $this->assertSame('7000', $route->squawk);
        $this->assertSame(36.71, (float) $route->lat);
        $this->assertSame(-6.41, (float) $route->lon);
    }

    #[Test]
    public function a_different_message_counter_creates_a_new_row(): void
    {
        $this->service->addAircraft(['icao' => 'MERGE02', 'lat' => 36.71, 'lon' => -6.41, 'messages' => 100]);
        $this->service->addAircraft(['icao' => 'MERGE02', 'lat' => 36.72, 'lon' => -6.42, 'messages' => 101]);

        $airplane = AirFlightAirPlane::where('icao', 'MERGE02')->firstOrFail();

        $this->assertSame(2, $airplane->routes()->count());
    }

    /**
     * `getDetectedQuery()` alimenta "Aviones detectados (última hora)".
     * Mode S manda cada dato en un mensaje distinto, así que la última fila
     * de un avión casi siempre trae uno o dos campos y el resto a null. La
     * tabla necesita el último valor CONOCIDO de cada campo, juntando todas
     * las rutas de la ventana — no la última fila suelta.
     */
    #[Test]
    public function the_detected_query_merges_the_last_non_null_value_of_each_field_across_several_routes(): void
    {
        $airplane = AirFlightAirPlane::create(['icao' => 'AGREGADO', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'altitude' => 8000,
            'seen_at' => Carbon::now()->subMinutes(3),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $airplane->id,
            'squawk' => '7000',
            'seen_at' => Carbon::now()->subMinute(),
        ]);

        $result = $this->service->getDetectedQuery(Carbon::now()->subHour())->get();

        $this->assertCount(1, $result);

        $row = $result->first();
        $this->assertSame(8000.0, (float) $row->altitude);
        $this->assertSame('7000', $row->squawk);
    }
}
