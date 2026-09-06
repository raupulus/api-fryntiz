<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use App\Support\Auth\TokenAbilities;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/weatherstation/{sensor}/store — los 11 sensores + el lote.
 *
 * `WeatherStationTest` tenía 22 tests y ninguno comprobaba que la lectura
 * quedara guardada: todos eran de 401 y de 422 (N279).
 *
 * OJO con el data provider: sólo temperature, humidity, pressure, eco2 y tvoc
 * usan el campo `value` (StoreSensorRequest). Los otros seis tienen cada uno
 * sus propios campos, y ahí es donde está el problema gordo — ver
 * `a_missing_required_database_field_blows_up_with_500`.
 */
class WeatherStationPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Estación de Chipiona (pruebas)',
        ]);
    }

    /**
     * ruta => [modelo, payload completo (todas las columnas NOT NULL cubiertas)]
     *
     * @return array<string,array{0:string,1:class-string<Model>,2:array<string,mixed>}>
     */
    public static function sensors(): array
    {
        return [
            // StoreSensorRequest: campo único `value`.
            'temperatura' => ['temperatures', Temperature::class, ['value' => 21.4]],
            'humedad' => ['humidities', Humidity::class, ['value' => 63.2]],
            'presión' => ['pressures', Pressure::class, ['value' => 1013.7]],
            'eco2' => ['eco2-readings', Eco2::class, ['value' => 415.0]],
            'tvoc' => ['tvoc-readings', Tvoc::class, ['value' => 120.0]],

            // FormRequest propio, campos propios.
            'luz' => ['lights', Light::class, [
                'lumens' => 842.0,
                'lux' => 9100.0,
                'index' => 4.0,
                'uva' => 1.2,
                'uvb' => 0.4,
            ]],
            'lluvia' => ['rains', Rain::class, [
                'rain' => 0.4,
                'rain_intensity' => 1.2,
                'rain_month' => 18.6,
                'moisture' => 9.3,
            ]],
            'viento' => ['winds', Wind::class, [
                'speed' => 12.4,
                'average' => 9.8,
                'min' => 3.1,
                'max' => 21.7,
            ]],
            'dirección del viento' => ['wind-directions', WindDirection::class, [
                'grades' => 270,
                'direction' => 'W',
                'resistance' => 3300.0,
            ]],
            'rayos' => ['lightnings', Lightning::class, [
                'distance' => 12,
                'energy' => 140000,
                'noise_floor' => 2,
            ]],
            'calidad del aire' => ['air-qualities', AirQuality::class, [
                'gas_resistance' => 52000.0,
                'air_quality' => 87.5,
            ]],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string,mixed>  $payload
     */
    #[Test]
    #[DataProvider('sensors')]
    public function the_sensor_reading_is_stored(string $path, string $model, array $payload): void
    {
        $payload['hardware_device_id'] = $this->device->id;

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/{$path}"),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $row = $model::query()->latest('id')->first();

        $this->assertNotNull(
            $row,
            "La API respondió 201 y no hay ninguna fila en `{$path}`."
        );

        $this->assertPersisted($row, $payload);
    }

    /**
     * El fallo sistémico de los seis sensores compuestos.
     *
     * `meteorology_light.lumens` y `.lux` son NOT NULL en la migración, pero
     * `StoreLightRequest` los declara `nullable`. Resultado: una petición sin
     * `lumens` pasa la validación, llega a `Light::create()` y PostgreSQL
     * revienta con 23502 → **500**, no 422.
     *
     * Lo mismo en rain (`rain`, `moisture`), wind (`speed`, `average`, `min`,
     * `max`), wind_direction (`grades`, `direction`), lightning (`distance`,
     * `energy`) y air_quality (`gas_resistance`, `air_quality`).
     *
     * Un microcontrolador al que se le muere un sensor tumba el endpoint en vez
     * de recibir un error entendible.
     */
    #[Test]
    #[DataProvider('unvalidatedRequiredFields')]
    public function a_missing_required_database_field_blows_up_with_500(
        string $path,
        array $payloadIncompleto,
        string $column
    ): void {
        $payloadIncompleto['hardware_device_id'] = $this->device->id;

        $response = $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/{$path}"),
            $payloadIncompleto,
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        );

        $this->assertSame(
            422,
            $response->status(),
            sprintf(
                "`%s` es NOT NULL en base de datos pero su FormRequest la declara `nullable`.\n".
                "Omitirla debería dar 422 y da %d.\n".
                "Arreglo: poner `required` en el FormRequest (o `nullable` en la columna, pero eso es peor: 0 y NULL no son lo mismo en un histórico).\n",
                $column,
                $response->status()
            )
        );
    }

    /**
     * @return array<string,array{0:string,1:array<string,mixed>,2:string}>
     */
    public static function unvalidatedRequiredFields(): array
    {
        return [
            'light sin lumens' => ['lights', ['lux' => 9100.0], 'meteorology_light.lumens'],
            // `lux` NO está aquí: en producción falta en el 6 % de las filas, así
            // que es opcional de verdad (D114).
            'rain sin rain' => ['rains', ['moisture' => 9.3], 'meteorology_rain.rain'],
            'wind sin speed' => ['winds', ['average' => 9.8, 'min' => 3.1, 'max' => 21.7], 'meteorology_winter.speed'],
            'wind-direction sin grades' => ['wind-directions', ['direction' => 'W'], 'meteorology_wind_direction.grades'],
            'lightning sin distance' => ['lightnings', ['energy' => 140000], 'meteorology_lightning.distance'],
            'air-quality sin gas_resistance' => ['air-qualities', ['air_quality' => 87.5], 'meteorology_air_quality.gas_resistance'],
        ];
    }

    #[Test]
    public function a_real_zero_is_stored_as_zero(): void
    {
        // Lluvia acumulada 0 mm es un dato: significa "no ha llovido", no
        // "no tengo dato". Si acaba en NULL, los acumulados salen mal.
        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/rains"),
            [
                'hardware_device_id' => $this->device->id,
                'rain' => 0,
                'rain_intensity' => 0,
                'rain_month' => 0,
                'moisture' => 0,
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $row = Rain::query()->latest('id')->first();

        $this->assertNotNull($row, 'Una lectura de lluvia con valor 0 no llegó a guardarse.');
        $this->assertNotNull($row->rain, 'La lluvia 0 se guardó como NULL: 0 mm y "sin dato" pasan a ser lo mismo.');
        $this->assertEqualsWithDelta(0.0, (float) $row->rain, 0.0001);
    }

    #[Test]
    public function the_generic_endpoint_stores_every_sensor_in_the_batch(): void
    {
        // El "genérico" es la escritura por lotes que usa el microcontrolador
        // para no gastar radio en 12 peticiones. Si pierde sensores por el
        // camino, no lo detecta ningún test de 401 ni de 422.
        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/readings"),
            [
                'hardware_device_id' => $this->device->id,
                'data' => [
                    'temperature' => [['value' => 19.8]],
                    'humidity' => [['value' => 71.0]],
                    'pressure' => [['value' => 1008.2]],
                    'light' => [['lumens' => 700.0, 'lux' => 8000.0]],
                    'rain' => [['rain' => 1.5, 'moisture' => 8.1]],
                ],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        foreach ([
            [Temperature::class, 'value', 19.8],
            [Humidity::class, 'value', 71.0],
            [Pressure::class, 'value', 1008.2],
            [Light::class, 'lumens', 700.0],
            [Rain::class, 'rain', 1.5],
        ] as [$model, $column, $expected]) {
            $row = $model::query()->latest('id')->first();

            $this->assertNotNull($row, "El lote se aceptó y no llegó nada a `{$model}`.");
            $this->assertEqualsWithDelta(
                $expected,
                (float) $row->{$column},
                0.0001,
                "El lote guardó `{$column}` cambiado en `{$model}`."
            );
        }
    }

    /**
     * El lote NO valida el contenido de cada sensor: `StoreGenericRequest` sólo
     * exige `data.* => array`. Así que por la puerta de atrás entra cualquier
     * cosa que por la puerta principal daría 422.
     */
    #[Test]
    public function the_batch_does_not_validate_the_contents_of_each_sensor(): void
    {
        $response = $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/readings"),
            [
                'hardware_device_id' => $this->device->id,
                'data' => [
                    'temperature' => [['value' => 'esto no es un número']],
                ],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        );

        $this->assertSame(
            422,
            $response->status(),
            sprintf(
                "`/generic/store` acepta un `value` no numérico (respondió %d).\n".
                "`StoreGenericRequest` sólo valida `data.* => array`; el contenido de cada\n".
                "sensor no pasa por su FormRequest. El lote es un agujero en la validación.\n",
                $response->status()
            )
        );
    }

    /**
     * La estación es la de la URL, y sólo la de la URL.
     *
     * `StoreSensorReadingsRequest::prepareForValidation()` reescribe
     * `hardware_device_id` con el de la ruta antes de validar, así que un
     * `hardware_device_id` en el cuerpo apuntando a la estación de otro no es
     * un 422: es ruido que se ignora. Lo que hay que comprobar es dónde acaba
     * la lectura.
     */
    #[Test]
    public function cannot_write_on_someone_elses_station(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $foreignOne = HardwareDevice::create(['user_id' => $other->id, 'name' => 'Estación ajena']);

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/temperatures"),
            ['hardware_device_id' => $foreignOne->id, 'value' => 20.0],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $this->assertSame(
            0,
            Temperature::query()->where('hardware_device_id', $foreignOne->id)->count(),
            'Se guardó una lectura en la estación de otro usuario.'
        );
        $this->assertSame(
            1,
            Temperature::query()->where('hardware_device_id', $this->device->id)->count(),
            'La lectura no acabó en la estación de la URL.'
        );
    }

    /**
     * El lote recibe `hardware_device_id` en la raíz y `storeGenericData()` lo
     * mete a la fuerza en cada registro — pero `OwnedHardwareDevice` sólo se
     * aplica a la raíz. Si además se cuela un `hardware_device_id` ajeno dentro
     * de `data`, hay que comprobar cuál gana.
     */
    #[Test]
    public function the_batch_ignores_a_foreign_device_placed_inside_data(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $foreignOne = HardwareDevice::create(['user_id' => $other->id, 'name' => 'Estación ajena']);

        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/readings"),
            [
                'hardware_device_id' => $this->device->id,
                'data' => [
                    'temperature' => [[
                        'hardware_device_id' => $foreignOne->id,
                        'value' => 33.3,
                    ]],
                ],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $row = Temperature::query()->latest('id')->first();

        $this->assertNotNull($row);
        $this->assertSame(
            $this->device->id,
            $row->hardware_device_id,
            'Una lectura se guardó contra un dispositivo que no es del usuario autenticado.'
        );
    }

    /**
     * WeatherStation era el módulo con más endpoints sin `hardware_device_info`
     * (12 de los 19 originales, AUDITORIA-HARDWARE-DEVICE-INFO.md): ni el sensor
     * individual ni el lote multi-sensor lo admitían.
     */
    #[Test]
    public function the_individual_sensor_endpoint_updates_the_device_status_when_hardware_device_info_is_sent(): void
    {
        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/temperatures"),
            [
                'hardware_device_id' => $this->device->id,
                'value' => 21.4,
                'hardware_device_info' => [
                    'battery_level' => 72,
                    'cpu' => 33.5,
                    'ram' => 41.25,
                    'ip_public' => '203.0.113.1',
                ],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
                + ['CF-Connecting-IP' => '198.51.100.7']
        )->assertStatus(201);

        $this->device->refresh();

        $this->assertSame(72, $this->device->battery_level);
        $this->assertEqualsWithDelta(33.5, (float) $this->device->cpu, 0.001);
        $this->assertEqualsWithDelta(41.25, (float) $this->device->ram, 0.001);
        $this->assertNotNull($this->device->last_seen_at);

        // La IP pública sale de la petición, no de lo que mande el cacharro.
        // El bloque agrupado se quedaba fuera de esto: sólo la resolvía el
        // endpoint dedicado de estado, así que las subidas de sensores
        // guardaban el estado sin IP ninguna.
        $this->assertSame('198.51.100.7', $this->device->ip_public);
    }

    #[Test]
    public function the_batch_endpoint_updates_the_device_status_when_hardware_device_info_is_sent(): void
    {
        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/readings"),
            [
                'hardware_device_id' => $this->device->id,
                'data' => ['temperature' => [['value' => 19.8]]],
                'hardware_device_info' => ['uptime' => 86400, 'ip_local' => '10.0.0.5'],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $this->device->refresh();

        $this->assertSame(86400, $this->device->uptime);
        $this->assertSame('10.0.0.5', $this->device->ip_local);
    }

    #[Test]
    public function a_reading_without_hardware_device_info_does_not_touch_the_device_status(): void
    {
        $this->postJson(
            $this->apiUrl("weather-stations/{$this->device->id}/temperatures"),
            ['hardware_device_id' => $this->device->id, 'value' => 21.4],
            $this->moduleHeaders($this->user, TokenAbilities::WEATHERSTATION_WRITE)
        )->assertStatus(201);

        $this->device->refresh();

        $this->assertNull($this->device->last_seen_at, 'Omitir `hardware_device_info` no debe tocar el estado del dispositivo.');
    }
}
