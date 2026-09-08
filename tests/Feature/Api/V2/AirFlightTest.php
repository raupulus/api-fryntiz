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
     * `index()` pagina el mismo `getDetectedQuery()` (query builder, no
     * Eloquent) — que la vista lo consuma con `$plane->campo` en vez de
     * `$plane->latestRoute->campo` sin explotar es justo lo que aquí se
     * comprueba.
     */
    #[Test]
    public function la_pagina_airflight_se_sirve_con_datos_agregados(): void
    {
        $avion = AirFlightAirPlane::create(['icao' => 'PAGINA1', 'seen_last_at' => Carbon::now()]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'flight' => 'IBE9999',
            'lat' => 36.7,
            'lon' => -6.4,
            'seen_at' => Carbon::now()->subMinutes(2),
        ]);

        $this->get(route('airflight.index'))
            ->assertOk()
            ->assertSee('PAGINA1')
            ->assertSee('IBE9999');
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

    /**
     * Reproduce el bug real reportado: la tabla salía casi toda con "-"
     * porque se leía sólo la última ruta del avión, y Mode S manda cada
     * dato en un mensaje distinto. El endpoint tiene que juntar el último
     * valor no nulo de cada campo entre todas las rutas de la última hora.
     */
    #[Test]
    public function la_tabla_de_detectados_junta_campos_repartidos_en_varias_rutas(): void
    {
        $avion = AirFlightAirPlane::create([
            'icao' => 'REPARTIDO',
            'seen_last_at' => Carbon::now(),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'altitude' => 9000,
            'speed' => 200,
            'seen_at' => Carbon::now()->subMinutes(4),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'squawk' => '2000',
            'seen_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->getJson(route('airflight.detected'))->assertOk();

        $fila = collect($response->json('data'))->firstWhere('icao', 'REPARTIDO');

        $this->assertNotNull($fila);
        // altitude ya está en metros, sólo se redondea. speed se pasa de
        // m/s a km/h para la tabla: 200 m/s * 3.6 = 720 km/h.
        $this->assertSame(9000, $fila['altitude']);
        $this->assertSame(720, $fila['speed']);
        $this->assertSame('2000', $fila['squawk']);
        $this->assertArrayNotHasKey('lat', $fila);
        $this->assertArrayNotHasKey('lon', $fila);
    }

    /**
     * `seen_last_at` tiene que llegar al frontend sin ambigüedad de zona
     * horaria: `getDetectedQuery()` no es Eloquent, así que el valor sale de
     * Postgres tal cual ("2026-09-08 09:29:34", sin zona). El navegador lo
     * convierte a la hora local del visitante (`formatFechaEsLocal` en la
     * vista); para eso necesita ISO-8601 con la zona explícita, no esa
     * cadena — algunos navegadores la interpretan como hora local en vez de
     * UTC, desplazando la hora mostrada.
     */
    #[Test]
    public function seen_last_at_llega_en_iso8601_utc_sin_ambiguedad(): void
    {
        $avion = AirFlightAirPlane::create([
            'icao' => 'FECHAUTC',
            'seen_last_at' => Carbon::create(2026, 9, 8, 9, 29, 34, 'UTC'),
        ]);

        AirFlightRoute::create([
            'airplane_id' => $avion->id,
            'squawk' => '1000',
            'seen_at' => Carbon::now()->subMinute(),
        ]);

        $fila = collect($this->getJson(route('airflight.detected'))->assertOk()->json('data'))
            ->firstWhere('icao', 'FECHAUTC');

        $this->assertNotNull($fila);
        $this->assertSame('2026-09-08T09:29:34.000000Z', $fila['seen_last_at']);
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
    public function store_aircraft_validates_vert_rate_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'vert_rate' => 500,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['vert_rate']);
    }

    #[Test]
    public function store_aircraft_validates_rssi_is_never_positive(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'rssi' => 5,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['rssi']);
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
