<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Wind;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class WeatherStationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * Crea un dispositivo de tipo "Estación Meteorológica" en la ubicación y
     * zona indicadas (el tipo se crea una sola vez).
     */
    private function makeStation(string $locationType, string $zone, string $name): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => $name,
            'location_type' => $locationType,
            'zone' => $zone,
        ]);
    }

    // ─── Endpoint de una estación ───

    /**
     * `GET /weather-stations` es una colección, así que `data` es una lista
     * aunque sólo venga una estación. Sin `?zone=` se devuelve la principal,
     * que es la primera de exterior.
     */
    #[Test]
    public function station_without_id_returns_first_outdoor(): void
    {
        $indoor = $this->makeStation('indoor', 'Salón', 'Salón');
        $outdoor = $this->makeStation('outdoor', 'Azotea', 'Azotea');

        Temperature::create(['hardware_device_id' => $indoor->id, 'value' => 22.0, 'created_at' => now()]);
        Temperature::create(['hardware_device_id' => $outdoor->id, 'value' => 38.0, 'created_at' => now()]);

        $response = $this->getJson($this->apiUrl('weather-stations'));

        $this->assertSuccessResponse($response);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($outdoor->id, $response->json('data.0.id'));
        $this->assertEquals('outdoor', $response->json('data.0.location_type'));
        $this->assertEquals(38.0, $response->json('data.0.temperature'));
    }

    #[Test]
    public function station_by_id_returns_that_station_formatted(): void
    {
        $indoor = $this->makeStation('indoor', 'Salón', 'Salón');
        $this->makeStation('outdoor', 'Azotea', 'Azotea');

        Temperature::create(['hardware_device_id' => $indoor->id, 'value' => 22.126, 'created_at' => now()]);

        $response = $this->getJson($this->apiUrl('weather-stations/'.$indoor->id));

        $this->assertSuccessResponse($response);
        $this->assertEquals($indoor->id, $response->json('data.id'));
        // Redondeo a 2 decimales y valor numérico (no cadena).
        $this->assertSame(22.13, $response->json('data.temperature'));
    }

    #[Test]
    public function station_formats_wind_in_kmh(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        Wind::create([
            'hardware_device_id' => $station->id,
            'speed' => 10.0, 'average' => 10.0, 'min' => 5.0, 'max' => 20.0,
            'created_at' => now(),
        ]);

        $response = $this->getJson($this->apiUrl('weather-stations/'.$station->id));

        $this->assertSuccessResponse($response);
        // 10 m/s * 3.6 = 36 km/h
        $this->assertEquals(36.0, $response->json('data.wind.average'));
        $this->assertEquals(72.0, $response->json('data.wind.max'));
    }

    #[Test]
    public function station_filters_by_requested_sensors(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 30.0, 'created_at' => now()]);

        $response = $this->getJson($this->apiUrl('weather-stations/'.$station->id.'?sensors=temperature,wind'));

        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data' => ['id', 'temperature', 'wind']]);
        $this->assertArrayNotHasKey('humidity', $response->json('data'));
        $this->assertArrayNotHasKey('lightning', $response->json('data'));
    }

    #[Test]
    public function station_rejects_invalid_sensor(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');

        $response = $this->getJson($this->apiUrl('weather-stations/'.$station->id.'?sensors=temperature,foo'));

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['sensors.1']);
    }

    #[Test]
    public function station_not_found_returns_404(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations/999999'));
        $this->assertErrorResponse($response, 404);
    }

    #[Test]
    /**
     * C3: la ventana de rayos es configurable —v1 contaba 10 minutos, v2 seis
     * horas, y ninguna de las dos valía para todo—. Se cuenta con la que diga
     * la configuración y se dice cuál es en la respuesta.
     */
    public function station_counts_lightning_within_last_six_hours(): void
    {
        config(['weather_station.lightning_window_minutes' => 360]);

        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');

        Lightning::create(['hardware_device_id' => $station->id, 'distance' => 10, 'energy' => 100, 'created_at' => now()->subHours(5)]);
        // Fuera de la ventana: no debe contar.
        Lightning::create(['hardware_device_id' => $station->id, 'distance' => 10, 'energy' => 100, 'created_at' => now()->subHours(7)]);

        $response = $this->getJson($this->apiUrl('weather-stations/'.$station->id));

        $this->assertSuccessResponse($response);
        $this->assertEquals(360, $response->json('data.lightning.window_minutes'));
        $this->assertEquals(1, $response->json('data.lightning.count_in_window'));
    }

    /**
     * Una colección sin resultados es un 200 con la lista vacía. El 404 es
     * para un recurso concreto que no existe, no para «no hay ninguno».
     */
    #[Test]
    public function non_weather_station_hardware_is_ignored(): void
    {
        // Hardware de exterior pero que NO es estación meteorológica.
        $type = HardwareType::create(['name' => 'PC Portátil']);
        HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => 'Portátil azotea',
            'location_type' => 'outdoor',
            'zone' => 'Azotea',
        ]);

        $response = $this->getJson($this->apiUrl('weather-stations'));

        $this->assertSuccessResponse($response);
        $this->assertSame([], $response->json('data'));
    }

    // ─── Endpoint por zona ───

    #[Test]
    public function zone_returns_collection_of_all_stations(): void
    {
        $this->makeStation('outdoor', 'Chipiona', 'Azotea 1');
        $this->makeStation('outdoor', 'Chipiona', 'Azotea 2');
        $this->makeStation('outdoor', 'Jardín', 'Otra');

        $response = $this->getJson($this->apiUrl('weather-stations?zone=Chipiona'));

        $this->assertSuccessResponse($response);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function zone_filters_by_location_type(): void
    {
        $this->makeStation('outdoor', 'Casa', 'Exterior casa');
        $this->makeStation('indoor', 'Casa', 'Interior casa');

        $response = $this->getJson($this->apiUrl('weather-stations?zone=Casa&location_type=indoor'));

        $this->assertSuccessResponse($response);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('indoor', $response->json('data.0.location_type'));
    }

    #[Test]
    public function zone_rejects_invalid_location_type(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations?zone=Casa&location_type=basement'));
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['location_type']);
    }

    #[Test]
    public function can_get_temperature(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures'));
        $this->assertSuccessResponse($response);
    }

    #[Test]
    public function can_get_humidity(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/humidities'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_get_pressure(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/pressures'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function temperature_index_accepts_date_range_filter(): void
    {
        $response = $this->getJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures?from=2025-01-01&to=2025-01-31'));
        $this->assertSuccessResponse($response);
    }

    // ─── POST stores (auth required) ───

    #[Test]
    public function cannot_store_temperature_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function cannot_store_humidity_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/humidities'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function cannot_store_pressure_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/pressures'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function cannot_store_generic_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_temperature_validates_required_device(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::WEATHERSTATION_WRITE);
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures'), [
            'value' => 23.5,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    /**
     * El cuerpo se normaliza siempre a un lote (`readings`), así que un cuerpo
     * vacío falla por ahí y no por `value`. Un `hardware_device_id` en el
     * cuerpo no pinta nada: la estación es la de la URL y se sobreescribe.
     */
    #[Test]
    public function store_temperature_validates_value_required(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::WEATHERSTATION_WRITE);

        $sinNada = $this->postJson(
            $this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures'),
            [],
            $headers
        );
        $this->assertErrorResponse($sinNada, 422);
        $sinNada->assertJsonValidationErrors(['readings']);

        $loteSinValor = $this->postJson(
            $this->apiUrl('weather-stations/'.$this->stationForTests().'/temperatures'),
            ['readings' => [[]]],
            $headers
        );
        $this->assertErrorResponse($loteSinValor, 422);
        $loteSinValor->assertJsonValidationErrors(['readings.0.value']);
    }

    #[Test]
    public function store_generic_validates_data_required(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::WEATHERSTATION_WRITE);
        $response = $this->postJson($this->apiUrl('weather-stations/'.$this->stationForTests().'/readings'), [
            'hardware_device_id' => 999,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }

    /**
     * Id de una estación con la que trabajar. Las lecturas cuelgan de su
     * estación, así que hace falta una para poder pedirlas.
     */
    private ?int $stationForTests = null;

    private function stationForTests(): int
    {
        return $this->estacionDePruebas ??= HardwareDevice::create([
            'user_id' => $this->createAuthenticatedUser()->id,
            'name' => 'Estación de pruebas',
        ])->id;
    }
}
