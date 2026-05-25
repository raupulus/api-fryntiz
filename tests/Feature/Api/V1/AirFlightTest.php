<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class AirFlightTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/airflight/v1';

    /** @test */
    public function can_get_aircrafts_json(): void
    {
        $response = $this->getJson($this->apiUrl('get/aircrafts/json'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_history_json(): void
    {
        $response = $this->getJson($this->apiUrl('get/history/json'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_receiver_json(): void
    {
        $response = $this->getJson($this->apiUrl('get/receiver/json'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_db_json(): void
    {
        $response = $this->getJson($this->apiUrl('get/db/json/recent'));
        $this->assertContains($response->status(), [200, 404]);
    }

    /** @test */
    public function cannot_register_aircraft_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('register/add'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_register_aircraft_batch_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('register/add-json'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }
}
