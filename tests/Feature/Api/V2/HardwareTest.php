<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

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
        $response->assertJsonValidationErrors(['hardware_device']);
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
        $response->assertJsonValidationErrors(['device_id']);
    }
}
