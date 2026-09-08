<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Support\Auth\TokenAbilities;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class AirFlightTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * Cabeceras de un cliente con permiso de lectura.
     *
     * Las lecturas de la API dejaron de ser públicas el 2026-09-06: el mapa de
     * `/airflight` se sirve desde el bloque web, ya cacheado.
     *
     * @return array<string, string>
     */
    private function lectura(): array
    {
        return $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_READ);
    }

    #[Test]
    public function las_lecturas_de_la_api_exigen_token(): void
    {
        $this->getJson($this->apiUrl('airflight/aircrafts'))->assertUnauthorized();
        $this->getJson($this->apiUrl('airflight/receiver'))->assertUnauthorized();
    }

    #[Test]
    public function un_token_de_escritura_no_lee(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);

        $this->getJson($this->apiUrl('airflight/aircrafts'), $headers)->assertForbidden();
    }

    /**
     * El mapa de `/airflight` es una página propia: se sirve desde el bloque
     * web, sin token y cacheado. La API es para integraciones.
     */
    #[Test]
    public function el_mapa_web_se_sirve_sin_token(): void
    {
        $this->getJson(route('airflight.aircrafts'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);

        $this->getJson(route('airflight.receiver'))
            ->assertOk()
            ->assertJsonPath('data.refresh', 5000);
    }

    /**
     * Tabla "Aviones detectados (última hora)" de `/airflight`: sondeo cada
     * minuto desde el propio frontend, sin token, y sólo con lo visto dentro
     * de la última hora.
     */
    #[Test]
    public function la_tabla_de_detectados_se_sirve_sin_token_y_filtra_por_ultima_hora(): void
    {
        $reciente = AirFlightAirPlane::create([
            'icao' => 'ABC123',
            'seen_last_at' => Carbon::now()->subMinutes(10),
            'seen_first_at' => Carbon::now()->subMinutes(15),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $reciente->id,
            'flight' => 'IBE1234',
            'lat' => 36.73,
            'lon' => -6.43,
            'altitude' => 10000,
            'speed' => 420,
            'track' => 90,
            'squawk' => '1000',
            'seen_at' => Carbon::now()->subMinutes(10),
        ]);

        $antiguo = AirFlightAirPlane::create([
            'icao' => 'OLD999',
            'seen_last_at' => Carbon::now()->subHours(3),
            'seen_first_at' => Carbon::now()->subHours(3),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $antiguo->id,
            'flight' => 'OLD999',
            'seen_at' => Carbon::now()->subHours(3),
        ]);

        $response = $this->getJson(route('airflight.detected'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $icaos = collect($response->json('data'))->pluck('icao');

        $this->assertContains('ABC123', $icaos);
        $this->assertNotContains('OLD999', $icaos);
    }

    #[Test]
    public function can_get_aircrafts(): void
    {
        $response = $this->getJson($this->apiUrl('airflight/aircrafts'), $this->lectura());
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_get_history(): void
    {
        $response = $this->getJson($this->apiUrl('airflight/aircrafts'), $this->lectura());
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_store_aircraft_authenticated(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['message', 'data']);
    }

    #[Test]
    public function cannot_store_aircraft_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_aircraft_validates_lat_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'lat' => 100,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lat']);
    }

    #[Test]
    public function store_aircraft_validates_lon_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'lon' => -200,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lon']);
    }

    #[Test]
    public function can_store_batch_authenticated(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [
            'data' => [['icao' => 'ABC123'], ['icao' => 'DEF456']],
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['data' => ['count']]);
    }

    #[Test]
    public function cannot_store_batch_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_batch_validates_data_required(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }

    #[Test]
    public function store_batch_validates_data_must_be_array(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), ['data' => 'not-array'], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }

    // ─── Receptor ADS-B (GET /airflight/receiver) ───

    #[Test]
    public function receiver_returns_the_map_configuration(): void
    {
        // Es pública y sin base de datos detrás: devuelve la configuración fija
        // que el mapa necesita para centrarse y refrescar.
        $response = $this->getJson($this->apiUrl('airflight/receiver'), $this->lectura());

        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data' => ['history', 'lat', 'lon', 'refresh', 'version']]);
    }

    #[Test]
    public function receiver_reports_history_disabled(): void
    {
        // No se guardan snapshots temporales, sólo la última posición de cada
        // avión, así que el mapa no debe ofrecer reproducción de recorrido.
        $this->getJson($this->apiUrl('airflight/receiver'), $this->lectura())
            ->assertJsonPath('data.history', 0);
    }
}
