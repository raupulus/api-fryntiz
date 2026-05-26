<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherStationApiTest extends TestCase
{
    public function test_resume_endpoint_returns_expected_structure(): void
    {
        $response = $this->getJson('/api/weatherstation/v1/resume');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'temperature',
            'humidity',
            'pressure',
            'wind_direction',
            'wind_average',
            'wind_min',
            'wind_max',
            'lightningQuantityLastTenMinutes',
            'instant' => [
                'timestamp',
                'year',
                'month',
                'day',
                'day_name',
                'time',
            ],
        ]);
    }

    public function test_generic_add_requires_authentication(): void
    {
        $response = $this->postJson('/api/weatherstation/v1/generic/add/json', [
            'hardware_device_id' => 1,
            'temperature' => 22.5,
        ]);

        $response->assertStatus(401);
    }

    public function test_generic_add_works_with_valid_token(): void
    {
        $user = User::first();

        if (! $user) {
            $this->markTestSkipped('No hay usuarios en la base de datos de test.');
        }

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/weatherstation/v1/generic/add/json', [
            'hardware_device_id' => $user->hardwareDevices()->first()?->id ?? 1,
            'temperature' => 22.5,
        ]);

        // Si el device no pertenece al usuario, da 400
        // Si pertenece, da 200 con message OK
        $response->assertStatus(200)->assertJson(['message' => 'OK']);
    }
}
