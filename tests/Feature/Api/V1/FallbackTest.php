<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class FallbackTest extends ApiTestCase
{
    /** @test */
    public function v1_general_fallback_returns_404_json(): void
    {
        $response = $this->getJson('/api/v1/endpoint-que-no-existe');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function airflight_v1_fallback_returns_404(): void
    {
        $response = $this->getJson('/api/airflight/v1/no-existe');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function hardware_v1_fallback_returns_404(): void
    {
        $response = $this->getJson('/api/hardware/v1/no-existe');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function weatherstation_v1_fallback_returns_404(): void
    {
        $response = $this->getJson('/api/weatherstation/v1/no-existe');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }
}
