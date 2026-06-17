<?php

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;

use Tests\Feature\Api\ApiTestCase;

class AirFlightTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function can_get_aircrafts(): void
    {
        $response = $this->getJson($this->apiUrl('airflight/aircrafts'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_get_history(): void
    {
        $response = $this->getJson($this->apiUrl('airflight/history'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_store_aircraft_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register'), [
            'icao' => 'ABC123',
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['message', 'data']);
    }

    #[Test]
    public function cannot_store_aircraft_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/register'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_aircraft_validates_lat_range(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register'), [
            'icao' => 'ABC123',
            'lat' => 100,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lat']);
    }

    #[Test]
    public function store_aircraft_validates_lon_range(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register'), [
            'icao' => 'ABC123',
            'lon' => -200,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lon']);
    }

    #[Test]
    public function can_store_batch_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register/batch'), [
            'data' => [['icao' => 'ABC123'], ['icao' => 'DEF456']],
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['data' => ['count']]);
    }

    #[Test]
    public function cannot_store_batch_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/register/batch'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_batch_validates_data_required(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register/batch'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }

    #[Test]
    public function store_batch_validates_data_must_be_array(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('airflight/register/batch'), ['data' => 'not-array'], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }
}
