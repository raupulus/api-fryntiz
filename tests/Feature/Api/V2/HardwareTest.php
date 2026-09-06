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
        $response = $this->postJson($this->apiUrl('hardware/energy-readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_energy_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::HARDWAREENERGY_WRITE);
        $response = $this->postJson($this->apiUrl('hardware/energy-readings'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_store_solar_reading_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('hardware/solar-readings'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_solar_reading_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::HARDWAREENERGY_WRITE);
        $response = $this->postJson($this->apiUrl('hardware/solar-readings'), [], $headers);
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
