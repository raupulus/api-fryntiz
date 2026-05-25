<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class FallbackTest extends ApiTestCase
{
    /** @test */
    public function v2_fallback_returns_standard_error_structure(): void
    {
        $response = $this->getJson('/api/v2/endpoint-que-no-existe');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'API V2 - Endpoint no encontrado',
            ]);
    }
}
