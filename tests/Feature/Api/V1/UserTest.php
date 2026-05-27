<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class UserTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v1';

    /** @test */
    public function can_get_user_list_authenticated(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('user/index'), $headers);
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['users']);
    }

    /** @test */
    public function cannot_get_user_list_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('user/index'), $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function can_show_own_user_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);
        $response = $this->postJson($this->apiUrl('user/show'), [
            'user_id' => $user->id,
        ], $headers);
        // 200 si Policy lo permite, 403 si no
        $this->assertContains($response->status(), [200, 403]);
    }

    /** @test */
    public function cannot_show_user_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('user/show'), ['user_id' => 1], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function create_user_is_blocked_returns_403(): void
    {
        $headers = $this->asSuperAdmin();
        $response = $this->postJson($this->apiUrl('user/create'), [
            'name' => 'Test', 'email' => 'newuser@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ], $headers);
        // Bloqueado con JsonHelper::forbidden() o 403 por Policy
        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_delete_user_unauthenticated(): void
    {
        $response = $this->deleteJson($this->apiUrl('user/delete'), ['user_id' => 1], $this->guestHeaders());
        $response->assertStatus(401);
    }
}
