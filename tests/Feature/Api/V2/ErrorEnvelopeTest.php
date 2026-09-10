<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Ninguna respuesta de error de la API se sale del envelope.
 *
 * Antes de la revisión de 2026-09-02 sólo lo cumplían los errores que tenían su
 * `render()` propio (401, 403, 404, 405, 422). Todo lo demás salía con la forma
 * de Laravel, y en **HTML** cuando el cliente no mandaba
 * `Accept: application/json` — que es justo lo que hace un microcontrolador
 * (auditoría AR-E02).
 *
 * Los dos casos que se escapaban y que ahora se fijan aquí:
 *
 *  · **429** del throttle, que lo provoca cualquiera insistiendo y que con
 *    APP_DEBUG=true llegaba a devolver el stack trace completo con rutas
 *    absolutas del sistema de ficheros.
 *  · **500** de un fallo no controlado.
 *
 * Cada caso se prueba con las dos cabeceras `Accept` que se ven en la vida
 * real: la de una web (`application/json`) y la de un cacharro (comodín).
 */
class ErrorEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    /** Cabeceras `Accept` que se prueban en todos los casos. */
    private const ACCEPTS = [
        'json' => 'application/json',
        'comodín' => '*/*',
        'ninguno' => '',
    ];

    /**
     * Comprueba que una ruta devuelve el envelope con las tres cabeceras.
     */
    private function assertEnvelopeWithAnyAccept(
        string $method,
        string $uri,
        int $status,
        array $payload = []
    ): void {
        foreach (self::ACCEPTS as $label => $accept) {
            // El idioma se fija a propósito: el cliente HTTP de las pruebas
            // manda `Accept-Language: en-us` por su cuenta, y desde que los
            // mensajes del envelope se traducen eso decide el texto.
            $headers = ['Accept-Language' => 'es'] + ($accept === '' ? [] : ['Accept' => $accept]);

            $response = $this->call($method, $uri, $payload, [], [], $this->transformHeadersToServerVars($headers));

            $this->assertSame(
                $status,
                $response->getStatusCode(),
                "Con Accept «{$label}», {$method} {$uri} debía responder {$status}."
            );

            $this->assertStringContainsString(
                'application/json',
                (string) $response->headers->get('Content-Type'),
                "Con Accept «{$label}», {$method} {$uri} no ha respondido JSON."
            );

            $body = json_decode((string) $response->getContent(), true);

            $this->assertIsArray($body, "Con Accept «{$label}», el cuerpo no es JSON válido.");
            $this->assertArrayHasKey('success', $body, "Con Accept «{$label}», falta la clave «success».");
            $this->assertFalse($body['success'], "Con Accept «{$label}», «success» debería ser false.");
            $this->assertArrayHasKey('message', $body, "Con Accept «{$label}», falta la clave «message».");

            // La forma de Laravel, que es de lo que se venía: nunca debe salir.
            $this->assertArrayNotHasKey('exception', $body, "Con Accept «{$label}», se ha filtrado «exception» a la raíz.");
            $this->assertArrayNotHasKey('trace', $body, "Con Accept «{$label}», se ha filtrado «trace».");
            $this->assertArrayNotHasKey('file', $body, "Con Accept «{$label}», se ha filtrado «file».");
        }
    }

    public function test_nonexistent_endpoint(): void
    {
        $this->assertEnvelopeWithAnyAccept('GET', '/api/v2/no-existe-esto', 404);
    }

    public function test_nonexistent_endpoint_via_post(): void
    {
        $this->assertEnvelopeWithAnyAccept('POST', '/api/v2/no-existe-esto', 404);
    }

    public function test_unauthenticated(): void
    {
        $this->assertEnvelopeWithAnyAccept('GET', '/api/v2/users/me', 401);
    }

    public function test_failed_validation(): void
    {
        $this->assertEnvelopeWithAnyAccept('POST', '/api/v2/auth/tokens', 422);
    }

    public function test_api_v1_removed(): void
    {
        $this->assertEnvelopeWithAnyAccept('GET', '/api/v1/lo-que-sea', 410);
    }

    /**
     * El caso que motivó todo esto: un 500 no controlado.
     */
    public function test_uncontrolled_error(): void
    {
        Route::middleware('api')->get('/api/test-boom', function () {
            throw new RuntimeException('detalle interno que no debe salir');
        });

        config(['app.debug' => false]);

        foreach (['application/json', '*/*'] as $accept) {
            $response = $this->call('GET', '/api/test-boom', [], [], [], [
                'HTTP_ACCEPT' => $accept,
                'HTTP_ACCEPT_LANGUAGE' => 'es',
            ]);

            $this->assertSame(500, $response->getStatusCode());
            $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

            $body = json_decode((string) $response->getContent(), true);

            $this->assertFalse($body['success']);
            $this->assertSame(__('api.server_error', [], 'es'), $body['message']);

            // Sin APP_DEBUG no se filtra nada del fallo real.
            $this->assertArrayNotHasKey('debug', $body);
            $this->assertStringNotContainsString('detalle interno', (string) $response->getContent());
        }
    }

    /**
     * Con APP_DEBUG el detalle aparece, pero dentro de `debug` y sólo ahí.
     */
    public function test_uncontrolled_error_in_development_carries_a_debug_block(): void
    {
        Route::middleware('api')->get('/api/test-boom-debug', function () {
            throw new RuntimeException('detalle interno para depurar');
        });

        config(['app.debug' => true]);

        $response = $this->withHeaders(['Accept-Language' => 'es'])->getJson('/api/test-boom-debug');
        $body = $response->json();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame(__('api.server_error', [], 'es'), $body['message']);

        $this->assertArrayHasKey('debug', $body);
        $this->assertSame(RuntimeException::class, $body['debug']['exception']['class']);
        $this->assertSame('detalle interno para depurar', $body['debug']['exception']['message']);
    }

    /**
     * El 429 del throttle: lo provoca cualquiera y se salía del contrato.
     */
    public function test_too_many_requests(): void
    {
        Route::middleware(['api', 'throttle:1,1'])->get('/api/test-throttle', fn () => response()->json(['ok' => true]));

        // La primera pasa; la segunda ya la corta el limitador.
        $this->getJson('/api/test-throttle');

        foreach (['application/json', '*/*'] as $accept) {
            $response = $this->call('GET', '/api/test-throttle', [], [], [], [
                'HTTP_ACCEPT' => $accept,
                'HTTP_ACCEPT_LANGUAGE' => 'es',
            ]);

            $this->assertSame(429, $response->getStatusCode());
            $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

            $body = json_decode((string) $response->getContent(), true);

            $this->assertFalse($body['success']);
            $this->assertSame(__('api.too_many_requests', [], 'es'), $body['message']);
            $this->assertArrayNotHasKey('trace', $body);

            // El cliente necesita saber cuánto esperar: la cabecera de la
            // excepción HTTP tiene que sobrevivir al envelope.
            $this->assertNotNull(
                $response->headers->get('Retry-After'),
                'El 429 ha perdido la cabecera Retry-After al pasar por el envelope.'
            );
        }
    }

    /**
     * Un borrado sigue siendo 204 sin cuerpo: es la única excepción consciente
     * a «todas las respuestas llevan envelope», y está decidida.
     */
    public function test_a_deletion_is_still_204_without_body(): void
    {
        Route::middleware('api')->delete('/api/test-delete', function () {
            return response()->json(null, 204);
        });

        $response = $this->deleteJson('/api/test-delete');

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
    }
}
