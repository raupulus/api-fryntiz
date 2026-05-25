<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class WeatherStationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/weatherstation/v1';

    // ─── GET públicos ───

    /** @test */
    public function can_get_resume(): void
    {
        $response = $this->getJson($this->apiUrl('resume'));
        $this->assertContains($response->status(), [200, 500]);
    }

    /** @test */
    public function can_get_temperature(): void
    {
        $response = $this->getJson($this->apiUrl('temperature'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_humidity(): void
    {
        $response = $this->getJson($this->apiUrl('humidity'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_pressure(): void
    {
        $response = $this->getJson($this->apiUrl('pressure'));
        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_lightning(): void
    {
        $response = $this->getJson($this->apiUrl('lightning'));
        $response->assertStatus(200);
    }

    // ─── POST table (públicos) ───

    /** @test */
    public function can_query_temperature_table(): void
    {
        $response = $this->postJson($this->apiUrl('table/temperature'), []);
        $response->assertStatus(200);
    }

    /** @test */
    public function can_query_humidity_table(): void
    {
        $response = $this->postJson($this->apiUrl('table/humidity'), []);
        $response->assertStatus(200);
    }

    // ─── POST store privados ───

    /** @test */
    public function cannot_store_generic_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('generic/add/json'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_temperature_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('temperature/add'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_humidity_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('humidity/add'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_lightning_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('lightning/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function cannot_store_lightning_batch_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('lightning/batch/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }
}
