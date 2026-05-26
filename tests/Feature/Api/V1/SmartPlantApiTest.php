<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class SmartPlantApiTest extends TestCase
{
    public function test_smartplant_url_prefix_is_correct(): void
    {
        // Sin auth debe dar 401, no 404
        $response = $this->postJson('/api/smartplant/v1/register/store', []);

        $this->assertNotEquals(404, $response->status(), 'El endpoint /api/smartplant/v1/register/store devuelve 404 — el prefijo de URL es incorrecto.');
        $response->assertStatus(401);
    }

    public function test_old_smart_plant_url_does_not_work(): void
    {
        // El prefijo antiguo con guión bajo NO debe funcionar (404 o 405)
        $response = $this->postJson('/api/smart_plant/v1/register/store', []);

        $this->assertTrue(
            in_array($response->status(), [404, 405]),
            'El endpoint antiguo /api/smart_plant/v1/register/store debería devolver 404 o 405, pero devolvió ' . $response->status()
        );
    }
}
