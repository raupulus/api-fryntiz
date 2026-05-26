<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ValidationFormatTest extends TestCase
{
    public function test_validation_error_returns_legacy_format(): void
    {
        $user = User::first();

        if (! $user) {
            $this->markTestSkipped('No hay usuarios en la base de datos de test.');
        }

        Sanctum::actingAs($user);

        // Enviar request incompleto al keyboard store
        $response = $this->postJson('/api/keycounter/v1/keyboard/store', []);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status',
            'message',
            'errors',
        ]);
        $response->assertJson([
            'status' => 'ko',
            'message' => 'The given data was invalid.',
        ]);
    }

    public function test_validation_error_contains_code_field(): void
    {
        $user = User::first();

        if (! $user) {
            $this->markTestSkipped('No hay usuarios en la base de datos de test.');
        }

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/keycounter/v1/keyboard/store', []);

        $response->assertStatus(422);
        // El formato legacy incluye codeError a través de JsonHelper::failed()
        $response->assertJsonStructure([
            'codeError',
            'httpCode',
        ]);
    }
}
