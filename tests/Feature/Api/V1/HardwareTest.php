<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class HardwareTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/hardware/v1';

    /** @test */
    public function can_get_device_info_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('get/device/1/info'), $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function cannot_get_device_info_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('get/device/1/info'), $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function can_get_computers_list_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('get/computers/list'), $headers);
        $response->assertStatus(200);
    }

    /** @test */
    public function cannot_get_computers_list_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('get/computers/list'), $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_energy_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('energy-monitor/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_solar_charge_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('solarcharge/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }
}
