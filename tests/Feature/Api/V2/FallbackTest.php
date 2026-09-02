<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class FallbackTest extends ApiTestCase
{
    /**
     * El idioma se pide explícitamente.
     *
     * Desde que los mensajes del envelope salen de `lang/{es,en}/api.php`
     * (revisión 2026-09-02), la respuesta respeta `Accept-Language`, que es
     * justo para lo que está `App\Http\Middleware\SetLocale`. Antes el texto
     * estaba escrito a mano en español dentro de `routes/api/v2.php` y salía
     * igual dijera lo que dijera el cliente.
     *
     * Conviene saber que **el cliente HTTP de las pruebas manda
     * `Accept-Language: en-us,en;q=0.5` por su cuenta**, así que un test que no
     * fije el idioma comprueba la traducción inglesa sin pretenderlo.
     */
    #[Test]
    public function v2_fallback_returns_standard_error_structure(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'es'])
            ->getJson('/api/v2/endpoint-que-no-existe');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'API V2 - Endpoint no encontrado',
            ]);
    }

    #[Test]
    public function v2_fallback_respects_accept_language(): void
    {
        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/v2/endpoint-que-no-existe')
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'API V2 - Endpoint not found',
            ]);
    }
}
