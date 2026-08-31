<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Support\Auth\TokenAbilities;
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
        $response = $this->getJson($this->apiUrl('airflight/aircrafts'));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function can_store_aircraft_authenticated(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['message', 'data']);
    }

    #[Test]
    public function cannot_store_aircraft_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_aircraft_validates_lat_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'lat' => 100,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lat']);
    }

    #[Test]
    public function store_aircraft_validates_lon_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts'), [
            'icao' => 'ABC123',
            'lon' => -200,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['lon']);
    }

    #[Test]
    public function can_store_batch_authenticated(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [
            'data' => [['icao' => 'ABC123'], ['icao' => 'DEF456']],
        ], $headers);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['data' => ['count']]);
    }

    #[Test]
    public function cannot_store_batch_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_batch_validates_data_required(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), [], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }

    #[Test]
    public function store_batch_validates_data_must_be_array(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::AIRFLIGHT_WRITE);
        $response = $this->postJson($this->apiUrl('airflight/aircrafts/batch'), ['data' => 'not-array'], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['data']);
    }
}
