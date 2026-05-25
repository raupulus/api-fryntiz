<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class SmartPlantTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/smart_plant/v1';

    /** @test */
    public function cannot_store_register_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('register/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function store_register_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('register/store'), [], $headers);
        // 422 por validación o 403 por Policy o 404 por plant_id no encontrado
        $this->assertContains($response->status(), [422, 403, 404]);
    }
}
