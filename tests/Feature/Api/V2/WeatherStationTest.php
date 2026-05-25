<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class WeatherStationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    // ─── GET públicos ───

    /** @test */
    public function can_get_resume(): void
    {
        $response = $this->getJson($this->apiUrl('weatherstation/resume'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data', 'message']);
    }

    /** @test */
    public function can_get_temperature(): void
    {
        $response = $this->getJson($this->apiUrl('weatherstation/temperature'));
        $this->assertSuccessResponse($response);
    }

    /** @test */
    public function can_get_humidity(): void
    {
        $response = $this->getJson($this->apiUrl('weatherstation/humidity'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function can_get_pressure(): void
    {
        $response = $this->getJson($this->apiUrl('weatherstation/pressure'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function temperature_index_accepts_date_range_filter(): void
    {
        $response = $this->getJson($this->apiUrl('weatherstation/temperature?from=2025-01-01&to=2025-01-31'));
        $this->assertSuccessResponse($response);
    }

    // ─── POST stores (auth required) ───

    /** @test */
    public function cannot_store_temperature_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weatherstation/temperature/store'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function cannot_store_humidity_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weatherstation/humidity/store'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function cannot_store_pressure_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weatherstation/pressure/store'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function cannot_store_generic_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('weatherstation/generic/store'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function store_temperature_validates_required_device(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('weatherstation/temperature/store'), [
            'value' => 23.5,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    /** @test */
    public function store_temperature_validates_value_required(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('weatherstation/temperature/store'), [
            'hardware_device_id' => 999,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['value']);
    }

    /** @test */
    public function store_generic_validates_data_required(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('weatherstation/generic/store'), [
            'hardware_device_id' => 999,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }
}
