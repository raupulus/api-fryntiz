<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\KeyCounter\Keyboard;
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
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_READ);
        $response = $this->getJson($this->apiUrl('keycounter/mouse-sessions?sort=pulsations'), $headers);
        $this->assertPaginatedResponse($response);
    }

    // ─── Lectura de sesiones de teclado (GET /keycounter/keyboard-sessions) ───

    #[Test]
    public function can_list_own_keyboard_sessions(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::KEYCOUNTER_READ);

        $response = $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $headers);

        $this->assertPaginatedResponse($response);
    }

    #[Test]
    public function keyboard_sessions_only_shows_your_own(): void
    {
        // La colección filtra por user_id: el listado de otro no debe asomar
        // aunque se pregunte con un token válido.
        $user = $this->createAuthenticatedUser();
        $otro = $this->createAuthenticatedUser();

        Keyboard::create([
            'user_id' => $otro->id,
            'pulsations' => 1234,
            'pulsations_special_keys' => 12,
            'pulsation_average' => 0.34,
            'score' => 10,
            'weekday' => (int) now()->dayOfWeek,
            'duration' => 3600,
            'start_at' => now()->subHour(),
            'end_at' => now(),
        ]);

        $response = $this->getJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->moduleHeaders($user, TokenAbilities::KEYCOUNTER_READ)
        );

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('meta.total'));
    }

    #[Test]
    public function cannot_list_keyboard_sessions_unauthenticated(): void
    {
        $this->assertErrorResponse(
            $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $this->guestHeaders()),
            401
        );
    }

    #[Test]
    public function un_token_de_escritura_no_puede_leer(): void
    {
        // AR-S02. Antes las GET se protegían con la ability de ESCRITURA, así
        // que el token que se graba en un teclado —cuyo único trabajo es subir
        // pulsaciones— también podía listar todas las sesiones de su dueño.
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_WRITE);

        $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $headers)->assertForbidden();
        $this->getJson($this->apiUrl('keycounter/mouse-sessions'), $headers)->assertForbidden();
    }

    #[Test]
    public function un_token_de_lectura_no_puede_escribir(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::KEYCOUNTER_READ);

        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [], $headers)->assertForbidden();
    }
}
