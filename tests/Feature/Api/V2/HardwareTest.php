<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class HardwareTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function can_get_device_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('hardware/device/1'), $headers);
        $this->assertContains($response->status(), [200, 404]);
        $response->assertJsonStructure(['success', 'message']);
    }

    #[Test]
    public function cannot_get_device_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('hardware/device/1'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function can_get_computers_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('hardware/computers'), $headers);
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function cannot_get_computers_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('hardware/computers'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function cannot_store_energy_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('hardware/energy'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_energy_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('hardware/energy'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_store_solar_charge_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('hardware/solar-charge'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_solar_charge_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('hardware/solar-charge'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_store_device_status_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('hardware/device-status'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_device_status_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('hardware/device-status'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function store_device_status_updates_last_known_state(): void
    {
        $user = $this->createAuthenticatedUser(3);
        $headers = $this->authenticatedHeaders($user);

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
            'extra' => ['ram' => 512],
        ];

        $response = $this->postJson($this->apiUrl('hardware/device-status'), $payload, $headers);
        $this->assertSuccessResponse($response);

        $device->refresh();
        $this->assertSame('192.168.1.100', $device->ip_local);
        $this->assertSame(48, $device->battery_level);
        $this->assertNotNull($device->last_seen_at);
        $this->assertSame(['ram' => 512], $device->extra);
    }

    #[Test]
    public function store_device_status_accepts_grouped_hardware_device_info(): void
    {
        $user = $this->createAuthenticatedUser(3);
        $headers = $this->authenticatedHeaders($user);

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

        $response = $this->postJson($this->apiUrl('hardware/device-status'), $payload, $headers);
        $this->assertSuccessResponse($response);

        $device->refresh();
        $this->assertSame(40.0, (float) $device->temp);
        $this->assertSame(999, $device->uptime);
    }
}
