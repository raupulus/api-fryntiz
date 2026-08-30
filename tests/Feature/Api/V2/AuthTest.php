<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class AuthTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function can_login_with_valid_credentials(): void
    {
        // 201 y no 200: crear un token es crear un recurso.
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['data' => ['token', 'expires_at', 'abilities', 'user']]);
        $response->assertHeader('Location');
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => 'noexiste@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertErrorResponse($response, 401);
        $response->assertJson(['message' => 'Credenciales inválidas']);
    }

    #[Test]
    public function login_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('auth/tokens'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    #[Test]
    public function login_rejects_invalid_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function login_rejects_short_password(): void
    {
        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => 'test@test.com',
            'password' => '123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function login_response_has_standard_structure(): void
    {
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['token', 'expires_at', 'abilities', 'user' => ['id', 'name']],
        ]);
    }

    #[Test]
    public function can_logout_authenticated(): void
    {
        // Cerrar sesión es borrar el token: DELETE, y 204 sin cuerpo.
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);

        $this->deleteJson($this->apiUrl('auth/tokens/current'), [], $headers)->assertStatus(204);

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function cannot_logout_unauthenticated(): void
    {
        $response = $this->deleteJson($this->apiUrl('auth/tokens/current'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function account_signup_and_deletion_do_not_exist_in_the_api(): void
    {
        // Se hacen desde el panel de Filament. `delete-account` borraba además
        // TODOS los tokens del usuario sin pedir contraseña, así que el token
        // de cualquier cacharro dejaba al dueño fuera (auditoría A1).
        $this->postJson($this->apiUrl('auth/signup'), [])->assertStatus(404);
        $this->postJson($this->apiUrl('auth/delete-account'), [], $this->asUser())->assertStatus(404);
        // Y la forma anterior del login tampoco: no hay capa de compatibilidad.
        $this->postJson($this->apiUrl('auth/login'), [])->assertStatus(404);
    }

    #[Test]
    public function the_login_token_cannot_write_in_any_module(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);

        $this->postJson($this->apiUrl('weather-stations/1/temperatures'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('airflight/aircrafts'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('hardware/energy-readings'), [], $headers)->assertStatus(403);
    }

    #[Test]
    public function a_device_token_cannot_close_the_owners_session(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::WEATHERSTATION_WRITE);

        $this->deleteJson($this->apiUrl('auth/tokens/current'), [], $headers)->assertStatus(403);
    }

    #[Test]
    public function a_deactivated_account_cannot_log_in(): void
    {
        $user = $this->createAuthenticatedUser();
        $user->forceFill(['is_active' => false])->save();

        $response = $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function deactivating_an_account_invalidates_its_existing_tokens(): void
    {
        // Es la palanca para cortar de golpe una cuenta comprometida sin tener
        // que ir borrando token a token.
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);

        $this->getJson($this->apiUrl('users/me'), $headers)->assertStatus(200);

        $user->forceFill(['is_active' => false])->save();

        $this->getJson($this->apiUrl('users/me'), $headers)->assertStatus(401);
    }

    #[Test]
    public function the_login_token_expires(): void
    {
        $user = $this->createAuthenticatedUser();

        $this->postJson($this->apiUrl('auth/tokens'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(201);

        $token = $user->tokens()->latest('id')->first();

        $this->assertNotNull($token->expires_at, 'El token de sesión humana debe caducar.');
        $this->assertSame([TokenAbilities::SESSION], $token->abilities);
    }
}
