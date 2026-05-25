<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

class KeyCounterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/keycounter/v1';

    /** @test */
    public function cannot_store_keyboard_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('keyboard/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function keyboard_store_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('keyboard/store'), [], $headers);
        // 422 por validación o 403 por Policy
        $this->assertContains($response->status(), [422, 403]);
    }

    /** @test */
    public function cannot_store_mouse_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('mouse/store'), [], $this->guestHeaders());
        $response->assertStatus(401);
    }

    /** @test */
    public function mouse_store_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('mouse/store'), [], $headers);
        // 422 por validación o 403 por Policy
        $this->assertContains($response->status(), [422, 403]);
    }
}
