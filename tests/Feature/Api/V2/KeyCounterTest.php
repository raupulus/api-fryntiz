<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class KeyCounterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function cannot_store_keyboard_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_keyboard_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_WRITE);
        $response = $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function store_keyboard_validates_weekday_range(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_WRITE);
        $response = $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [
            'weekday' => 7,
        ], $headers);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['weekday']);
    }

    #[Test]
    public function cannot_store_mouse_unauthenticated(): void
    {
        $response = $this->postJson($this->apiUrl('keycounter/mouse-sessions'), [], $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function store_mouse_validates_required_fields(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_WRITE);
        $response = $this->postJson($this->apiUrl('keycounter/mouse-sessions'), [], $headers);
        $this->assertErrorResponse($response, 422);
    }

    #[Test]
    public function index_mouse_ignores_sort_by_pulsations_instead_of_erroring(): void
    {
        // `keycounter_mouse` no tiene columna `pulsations` (esa sólo existe en
        // teclado). Un `?sort=pulsations` no declarado como sortable debe
        // ignorarse en silencio (CollectionQuery), nunca reventar contra la BD.
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_WRITE);
        $response = $this->getJson($this->apiUrl('keycounter/mouse-sessions?sort=pulsations'), $headers);
        $this->assertPaginatedResponse($response);
    }
}
