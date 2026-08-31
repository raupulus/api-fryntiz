<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/airflight/register y /register/batch.
 *
 * El receptor ADS-B manda lo que le da dump1090: identificador del avión más su
 * posición, altitud, rumbo y velocidad. En el esquema eso son **dos tablas**:
 *
 * - `airflight_airplanes` → el avión (icao, country, category, flag, ...)
 * - `airflight_routes`    → cada sondeo (lat, lon, altitude, speed, track, ...)
 *
 * `AirFlightService::addAircraft()` hace un único `AirFlightAirPlane::create()`.
 * Nunca toca `airflight_routes`. Y `AirFlightAirPlane::$fillable` no incluye
 * ninguno de los campos de posición.
 *
 * Estos tests comprueban qué sobrevive de verdad al viaje.
 */
class AirFlightPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
    }

    /**
     * Un sondeo tal cual lo manda dump1090.
     *
     * @return array<string,mixed>
     */
    private function probe(string $icao = '3444d2'): array
    {
        return [
            'icao' => $icao,
            'flight' => 'IBE3245 ',
            'squawk' => '7010',
            'lat' => 36.7412,
            'lon' => -6.4361,
            'altitude' => 11277.0,
            'speed' => 447.0,
            'track' => 218.0,
            'seen' => 0.4,
            'seen_pos' => 1.2,
            'messages' => 3184,
        ];
    }

    #[Test]
    public function the_aircraft_is_stored_with_its_icao(): void
    {
        $this->postJson(
            $this->apiUrl('airflight/aircrafts'),
            $this->probe(),
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $aircraft = AirFlightAirPlane::query()->latest('id')->first();

        $this->assertNotNull($aircraft, 'La API respondió 201 y no hay ninguna fila en `airflight_airplanes`.');
        $this->assertSame('3444d2', $aircraft->icao);
    }

    /**
     * El fallo gordo: de los 11 campos que valida `StoreAirFlightRequest`, sólo
     * `icao` está en `AirFlightAirPlane::$fillable`. Los otros 10 se van a
     * `create()` y Eloquent los descarta sin decir nada.
     */
    #[Test]
    public function the_aircraft_position_is_not_thrown_away(): void
    {
        $probe = $this->probe();

        $this->postJson(
            $this->apiUrl('airflight/aircrafts'),
            $probe,
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $aircraft = AirFlightAirPlane::query()->latest('id')->first();
        $this->assertNotNull($aircraft);

        // `airflight_routes` es donde viven lat/lon/altitude/speed/track.
        $path = AirFlightRoute::query()->where('airplane_id', $aircraft->id)->latest('id')->first();

        $this->assertNotNull($path, sprintf(
            "\nEl sondeo trae posición (lat %s, lon %s, altitud %s, rumbo %s, velocidad %s)\n".
            "y NO se ha creado ninguna fila en `airflight_routes`.\n\n".
            "`AirFlightService::addAircraft()` hace un único `AirFlightAirPlane::create(\$data)`.\n".
            "De los 11 campos validados, `\$fillable` sólo deja pasar `icao`; los otros 10\n".
            "(flight, squawk, lat, lon, altitude, speed, track, seen, seen_pos, messages)\n".
            "ni siquiera son columnas de `airflight_airplanes`: son de `airflight_routes`.\n\n".
            "Consecuencia: el receptor ADS-B manda posiciones y la API guarda sólo el hex.\n".
            "Y `AirFlightResource` las lee de `\$this->latestRoute`, que nunca existe, así que\n".
            "el mapa recibe lat/lon/altitude/speed nulos y `rssi` fijo en -100.\n",
            $probe['lat'], $probe['lon'], $probe['altitude'], $probe['track'], $probe['speed']
        ));

        $this->assertPersisted($path, [
            'lat' => $probe['lat'],
            'lon' => $probe['lon'],
            'altitude' => $probe['altitude'],
            'speed' => $probe['speed'],
            'track' => $probe['track'],
            'messages' => $probe['messages'],
            'squawk' => $probe['squawk'],
        ]);
    }

    /**
     * El receptor manda el mismo avión cada pocos segundos mientras esté a la
     * vista. Si cada sondeo crea una fila de avión nueva, `airflight_airplanes`
     * crece sin control y `/aircrafts` devuelve el mismo avión N veces.
     *
     * Además la tabla no tiene índice ninguno sobre `icao`.
     */
    #[Test]
    public function the_same_aircraft_reported_twice_is_not_duplicated(): void
    {
        foreach ([1, 2, 3] as $ignorado) {
            $this->postJson(
                $this->apiUrl('airflight/aircrafts'),
                $this->probe(),
                $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
            )->assertStatus(201);
        }

        $this->assertSame(
            1,
            AirFlightAirPlane::query()->where('icao', '3444d2')->count(),
            "Tres sondeos del mismo avión han creado tres filas en `airflight_airplanes`.\n".
            "`addAircraft()` hace `create()` a pelo, sin `updateOrCreate` ni `firstOrCreate`,\n".
            "y la tabla no tiene índice único sobre `icao`. Un avión a la vista 20 minutos\n".
            "genera cientos de filas duplicadas.\n"
        );
    }

    /**
     * `StoreAirFlightRequest` no valida ni `user_id` ni `hardware_device_id`, y
     * `addAircraft()` no los inyecta. Las columnas existen en la tabla.
     */
    #[Test]
    public function the_aircraft_is_linked_to_the_reporting_user(): void
    {
        $this->postJson(
            $this->apiUrl('airflight/aircrafts'),
            $this->probe(),
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $aircraft = AirFlightAirPlane::query()->latest('id')->first();

        $this->assertSame(
            $this->user->id,
            $aircraft?->user_id,
            "`airflight_airplanes.user_id` existe y se guarda NULL: ni el FormRequest lo\n".
            "valida ni el servicio lo inyecta. Ningún registro sabe qué receptor lo vio.\n"
        );
    }

    #[Test]
    public function the_batch_stores_every_aircraft_it_receives(): void
    {
        $lote = [
            $this->probe('3444d2'),
            $this->probe('45ac1f'),
            $this->probe('4ca7b3'),
        ];

        $this->postJson(
            $this->apiUrl('airflight/aircrafts/batch'),
            ['data' => $lote],
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $this->assertSame(
            3,
            AirFlightAirPlane::query()->count(),
            'El lote de 3 aviones no dejó 3 filas en `airflight_airplanes`.'
        );

        $this->assertSame(
            3,
            AirFlightRoute::query()->count(),
            "El lote de 3 sondeos con posición no dejó ninguna fila en `airflight_routes`.\n".
            "Mismo fallo que en /register, multiplicado por el tamaño del lote (hasta 500).\n"
        );
    }

    #[Test]
    public function without_a_token_nothing_can_be_stored(): void
    {
        $this->postJson($this->apiUrl('airflight/aircrafts'), $this->probe())
            ->assertStatus(401);
    }

    /**
     * AirFlight era uno de los siete endpoints IoT sin `hardware_device_info`
     * (AUDITORIA-HARDWARE-DEVICE-INFO.md), con la particularidad de que aquí
     * `hardware_device_id` es opcional: no todos los receptores lo mandan.
     */
    #[Test]
    public function the_receiver_status_is_updated_when_hardware_device_id_and_info_are_both_sent(): void
    {
        $receiver = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Receptor ADS-B']);

        $payload = array_merge($this->probe(), [
            'hardware_device_id' => $receiver->id,
            'hardware_device_info' => ['battery_level' => 65, 'temp' => 28.0],
        ]);

        $this->postJson(
            $this->apiUrl('airflight/aircrafts'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $receiver->refresh();

        $this->assertSame(65, $receiver->battery_level);
        $this->assertEqualsWithDelta(28.0, (float) $receiver->temp, 0.001);
        $this->assertNotNull($receiver->last_seen_at);
    }

    /**
     * Sin `hardware_device_id` no hay a quién aplicarle el estado: el
     * `hardware_device_info` se ignora en vez de romper la petición.
     */
    #[Test]
    public function hardware_device_info_without_a_device_id_does_not_break_the_request(): void
    {
        $payload = array_merge($this->probe(), [
            'hardware_device_info' => ['battery_level' => 65],
        ]);

        $this->postJson(
            $this->apiUrl('airflight/aircrafts'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $this->assertNotNull(AirFlightAirPlane::query()->latest('id')->first());
    }

    #[Test]
    public function the_batch_endpoint_also_updates_the_receiver_status(): void
    {
        $receiver = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Receptor ADS-B']);

        $this->postJson(
            $this->apiUrl('airflight/aircrafts/batch'),
            [
                'hardware_device_id' => $receiver->id,
                'hardware_device_info' => ['uptime' => 12345],
                'data' => [$this->probe('3444d2')],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::AIRFLIGHT_WRITE)
        )->assertStatus(201);

        $receiver->refresh();

        $this->assertSame(12345, $receiver->uptime);
    }
}
