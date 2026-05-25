<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class AuthTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    /** @test */
    public function csrf_cookie_returns_token(): void
    {
        $response = $this->getJson($this->apiUrl('auth/csrf-cookie'));
        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'csrf_token']);
    }

    /** @test */
    public function can_login_with_valid_credentials(): void
    {
        $user = $this->createAuthenticatedUser();
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'message']);
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), [
            'email'    => 'nadie@example.com',
            'password' => 'incorrecta',
        ]);
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthorized']);
    }

    /** @test */
    public function login_validates_required_fields(): void
    {
        $response = $this->postJson($this->apiUrl('auth/login'), []);
        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function signup_is_blocked_returns_403(): void
    {
        $response = $this->postJson($this->apiUrl('auth/signup'), [
            'name' => 'Test', 'email' => 'test@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function can_logout_when_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);
        $response = $this->postJson($this->apiUrl('auth/logout'), [], $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('status', 'ok');
    }

    /** @test */
    public function logout_fails_when_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('auth/logout'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }
}
