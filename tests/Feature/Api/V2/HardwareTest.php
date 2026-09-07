<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class HardwareTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * El detalle de un dispositivo puede traer su último estado conocido, con
     * la IP local y la pública, pidiéndolo con `?include=status`.
     */
    #[Test]
    public function el_detalle_incluye_el_estado_con_include_status(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Pico W',
            'ip_local' => '172.18.1.209',
            'ip_public' => '139.47.158.109',
            'ram' => 34.2,
            'uptime' => 86400,
        ]);

        $response = $this->getJson(
            $this->apiUrl("hardware/devices/{$device->id}?include=status"),
            $this->moduleHeaders($user, TokenAbilities::HARDWARE_READ)
        );

        $response->assertOk()
            ->assertJsonPath('data.status.ip_local', '172.18.1.209')
            ->assertJsonPath('data.status.ip_public', '139.47.158.109')
            ->assertJsonPath('data.status.uptime', 86400)
            ->assertJsonStructure(['data' => ['status' => [
                'hardware_device_id', 'temp', 'voltage', 'battery_level',
                'cpu', 'disk', 'ram', 'uptime', 'ip_local', 'ip_public',
                'extra', 'last_seen_at',
            ]]]);
    }

    /**
     * Sin el parámetro no salen las IPs: son datos del cacharro y no tienen por
     * qué viajar en cada respuesta del inventario.
     */
    #[Test]
    public function sin_include_status_el_detalle_no_trae_las_ips(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Pico W',
            'ip_local' => '172.18.1.209',
            'ip_public' => '139.47.158.109',
        ]);

        $response = $this->getJson(
            $this->apiUrl("hardware/devices/{$device->id}"),
            $this->moduleHeaders($user, TokenAbilities::HARDWARE_READ)
        );

        $response->assertOk()->assertJsonMissingPath('data.status');
        $response->assertDontSee('139.47.158.109');
    }

    #[Test]
    public function un_include_que_no_existe_responde_422(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Pico W']);

        $response = $this->getJson(
            $this->apiUrl("hardware/devices/{$device->id}?include=loquesea"),
            $this->moduleHeaders($user, TokenAbilities::HARDWARE_READ)
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['include.0']);
    }

    #[Test]
    public function can_get_device_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Portátil']);

        $response = $this->getJson(
            $this->apiUrl("hardware/devices/{$device->id}"),
            $this->moduleHeaders($user, TokenAbilities::HARDWARE_READ)
        );

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.id', $device->id);
    }

    #[Test]
    public function cannot_get_device_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('hardware/devices/1'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function can_get_computers_authenticated(): void
    {
        // `GET /hardware/computers` era una ruta propia para lo que es un
        // filtro de la colección.
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::HARDWARE_READ);
        $response = $this->getJson($this->apiUrl('hardware/devices?type=laptop'), $headers);
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page']]);
    }

    #[Test]
    public function cannot_get_computers_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('hardware/devices'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function cannot_store_energy_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('energy/readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_energy_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::ENERGY_WRITE);
        $response = $this->postJson($this->apiUrl('energy/readings'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_store_solar_reading_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('energy/solar-readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_solar_reading_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::ENERGY_WRITE);
        $response = $this->postJson($this->apiUrl('energy/solar-readings'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_store_device_status_unauthenticated(): void
    {
        $response = $this->putJson($this->apiUrl('hardware/devices/1/status'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_device_status_validates_required_fields(): void
    {
        // El dispositivo va en la URL, así que un id que no es del usuario
        // falla por pertenencia, no por campo obligatorio.
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::HARDWARE_WRITE);
        $response = $this->putJson($this->apiUrl('hardware/devices/999999/status'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function store_device_status_updates_last_known_state(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::HARDWARE_WRITE);

        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Test Device',
        ]);

        $payload = [
            'hardware_device_id' => $device->id,
            'temp' => 33,
            'voltage' => 3.7,
            'battery_level' => 48,
            'ip_local' => '192.168.1.100',
            'ip_public' => '203.0.113.1',
            'cpu' => 33,
            'uptime' => 123456,
            'disk' => 80,
            'ram' => 62.5,
            'extra' => ['swap' => 128],
        ];

        $response = $this->putJson($this->apiUrl("hardware/devices/{$device->id}/status"), $payload, $headers);
        $this->assertSuccessResponse($response);

        $device->refresh();
        $this->assertSame('192.168.1.100', $device->ip_local);
        $this->assertSame(48, $device->battery_level);
        $this->assertNotNull($device->last_seen_at);
        // La memoria tiene columna propia: antes sólo cabía dentro de `extra`,
        // que es JSON y no se puede ordenar ni graficar.
        $this->assertSame(62.5, $device->ram);
        $this->assertSame(['swap' => 128], $device->extra);

        // La IP pública la pone el servidor a partir de la petición, no el
        // dispositivo: lo que mande en `ip_public` se ignora. En las pruebas no
        // hay proxy ni IP pública, así que queda a null.
        $this->assertNull($device->ip_public);
    }

    #[Test]
    public function la_memoria_fuera_de_rango_se_rechaza(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::HARDWARE_WRITE);

        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Test Device']);

        $this->putJson(
            $this->apiUrl("hardware/devices/{$device->id}/status"),
            ['ram' => 140],
            $headers
        )->assertStatus(422);
    }

    #[Test]
    public function store_device_status_accepts_grouped_hardware_device_info(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::HARDWARE_WRITE);

        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Test Device',
        ]);

        $payload = [
            'hardware_device_id' => $device->id,
            'hardware_device_info' => [
                'temp' => 40,
                'voltage' => 4.1,
                'uptime' => 999,
            ],
        ];

        $response = $this->putJson($this->apiUrl("hardware/devices/{$device->id}/status"), $payload, $headers);
        $this->assertSuccessResponse($response);

        $device->refresh();
        $this->assertSame(40.0, (float) $device->temp);
        $this->assertSame(999, $device->uptime);
    }
}
