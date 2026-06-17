<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class KeyCounterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function cannot_store_keyboard_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('keycounter/keyboard'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_keyboard_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('keycounter/keyboard'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function store_keyboard_validates_weekday_range(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('keycounter/keyboard'), [
            'weekday' => 7,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['weekday']);
    }

    #[Test]
    public function cannot_store_mouse_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('keycounter/mouse'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_mouse_validates_required_fields(): void
    {
        $headers = $this->asUser();
        $response = $this->postJson($this->apiUrl('keycounter/mouse'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }
}
