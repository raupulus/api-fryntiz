<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class UserTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /** @test */
    public function admin_can_list_users(): void
    {
        $headers = $this->asSuperAdmin();
        $response = $this->getJson($this->apiUrl('user'), $headers);
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function regular_user_cannot_list_users(): void
    {
        $headers = $this->asUser();
        $response = $this->getJson($this->apiUrl('user'), $headers);
        $this->assertErrorResponse($response, 403);
    }

    /** @test */
    public function cannot_get_user_list_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('user'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function can_get_user_show_authenticated(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->authenticatedHeaders($user);
        $response = $this->getJson($this->apiUrl("user/{$user->id}"), $headers);
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data' => ['id', 'name']]);
    }

    /** @test */
    public function cannot_get_user_show_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('user/1'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function cannot_update_user_unauthenticated(): void
    {
        $response = $this->putJson($this->apiUrl('user/1'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    /** @test */
    public function cannot_destroy_user_unauthenticated(): void
    {
        $response = $this->deleteJson($this->apiUrl('user/1'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }
}
