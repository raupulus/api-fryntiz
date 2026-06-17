<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class SmartPlantTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function cannot_store_register_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('smartplant/register'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_register_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('smartplant/register'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function store_register_validates_soil_humidity_required(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('smartplant/register'), [
            'plant_id' => 1,
            'hardware_device_id' => 1,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['soil_humidity']);
    }
}
