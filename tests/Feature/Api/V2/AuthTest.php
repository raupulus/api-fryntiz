<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class AuthTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function can_login_with_valid_credentials(): void
    {
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data' => ['token', 'user']]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => 'noexiste@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertErrorResponse($response, 401);
        $response->assertJson(['message' => 'Credenciales invalidas']);
    }

    /** @test */
    public function login_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    /** @test */
    public function login_rejects_invalid_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => 'not-an-email',
            'password' => 'password123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function login_rejects_short_password(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => 'test@test.com',
            'password' => '123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function login_response_has_standard_structure(): void
    {
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['token', 'user' => ['id', 'name']],
        ]);
    }

    /** @test */
    public function can_signup_with_valid_data(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name'                  => 'Nuevo Usuario',
            'email'                 => 'nuevo@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->assertSuccessResponse($response, 201);
        $response->assertJsonStructure(['data' => ['token', 'user']]);
    }

    /** @test */
    public function signup_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function signup_rejects_duplicate_email(): void
    {
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name' => 'Otro', 'email' => $user->email,
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function signup_rejects_short_password(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name' => 'Test', 'email' => 'test@test.com',
            'password' => '123', 'password_confirmation' => '123',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function signup_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name' => 'Test', 'email' => 'test@test.com',
            'password' => 'password123', 'password_confirmation' => 'different',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function signup_creates_user_with_role_3(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name' => 'Nuevo', 'email' => 'nuevo@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $this->assertSuccessResponse($response, 201);
        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com', 'role_id' => 3]);
    }

    /** @test */
    public function can_logout_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);
        $response = $this->postJson($this->apiUrl('auth/logout'), [], $headers);
        $this->assertSuccessResponse($response);
        $response->assertJson(['message' => 'Sesion cerrada correctamente']);
    }

    /** @test */
    public function cannot_logout_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('auth/logout'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function can_delete_account_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);
        $response = $this->postJson($this->apiUrl('auth/delete-account'), [], $headers);
        $this->assertSuccessResponse($response);
        $response->assertJson(['message' => 'Cuenta eliminada correctamente']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function cannot_delete_account_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('auth/delete-account'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }
}
