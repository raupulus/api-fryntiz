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
    private function assertEnvelopeConCualquierAccept(
        string $method,
        string $uri,
        int $status,
        array $payload = []
    ): void {
        foreach (self::ACCEPTS as $etiqueta => $accept) {
            // El idioma se fija a propósito: el cliente HTTP de las pruebas
            // manda `Accept-Language: en-us` por su cuenta, y desde que los
            // mensajes del envelope se traducen eso decide el texto.
            $headers = ['Accept-Language' => 'es'] + ($accept === '' ? [] : ['Accept' => $accept]);

            $respuesta = $this->call($method, $uri, $payload, [], [], $this->transformHeadersToServerVars($headers));

            $this->assertSame(
                $status,
                $respuesta->getStatusCode(),
                "Con Accept «{$etiqueta}», {$method} {$uri} debía responder {$status}."
            );

            $this->assertStringContainsString(
                'application/json',
                (string) $respuesta->headers->get('Content-Type'),
                "Con Accept «{$etiqueta}», {$method} {$uri} no ha respondido JSON."
            );

            $cuerpo = json_decode((string) $respuesta->getContent(), true);

            $this->assertIsArray($cuerpo, "Con Accept «{$etiqueta}», el cuerpo no es JSON válido.");
            $this->assertArrayHasKey('success', $cuerpo, "Con Accept «{$etiqueta}», falta la clave «success».");
            $this->assertFalse($cuerpo['success'], "Con Accept «{$etiqueta}», «success» debería ser false.");
            $this->assertArrayHasKey('message', $cuerpo, "Con Accept «{$etiqueta}», falta la clave «message».");

            // La forma de Laravel, que es de lo que se venía: nunca debe salir.
            $this->assertArrayNotHasKey('exception', $cuerpo, "Con Accept «{$etiqueta}», se ha filtrado «exception» a la raíz.");
            $this->assertArrayNotHasKey('trace', $cuerpo, "Con Accept «{$etiqueta}», se ha filtrado «trace».");
            $this->assertArrayNotHasKey('file', $cuerpo, "Con Accept «{$etiqueta}», se ha filtrado «file».");
        }
    }

    public function test_endpoint_inexistente(): void
    {
        $this->assertEnvelopeConCualquierAccept('GET', '/api/v2/no-existe-esto', 404);
    }

    public function test_endpoint_inexistente_por_post(): void
    {
        $this->assertEnvelopeConCualquierAccept('POST', '/api/v2/no-existe-esto', 404);
    }

    public function test_sin_autenticar(): void
    {
        $this->assertEnvelopeConCualquierAccept('GET', '/api/v2/users/me', 401);
    }

    public function test_validacion_fallida(): void
    {
        $this->assertEnvelopeConCualquierAccept('POST', '/api/v2/auth/tokens', 422);
    }

    public function test_api_v1_eliminada(): void
    {
        $this->assertEnvelopeConCualquierAccept('GET', '/api/v1/lo-que-sea', 410);
    }

    /**
     * El caso que motivó todo esto: un 500 no controlado.
     */
    public function test_error_no_controlado(): void
    {
        Route::middleware('api')->get('/api/test-boom', function () {
            throw new RuntimeException('detalle interno que no debe salir');
        });

        config(['app.debug' => false]);

        foreach (['application/json', '*/*'] as $accept) {
            $respuesta = $this->call('GET', '/api/test-boom', [], [], [], [
                'HTTP_ACCEPT' => $accept,
                'HTTP_ACCEPT_LANGUAGE' => 'es',
            ]);

            $this->assertSame(500, $respuesta->getStatusCode());
            $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));

            $cuerpo = json_decode((string) $respuesta->getContent(), true);

            $this->assertFalse($cuerpo['success']);
            $this->assertSame(__('api.server_error', [], 'es'), $cuerpo['message']);

            // Sin APP_DEBUG no se filtra nada del fallo real.
            $this->assertArrayNotHasKey('debug', $cuerpo);
            $this->assertStringNotContainsString('detalle interno', (string) $respuesta->getContent());
        }
    }

    /**
     * Con APP_DEBUG el detalle aparece, pero dentro de `debug` y sólo ahí.
     */
    public function test_error_no_controlado_en_desarrollo_lleva_bloque_debug(): void
    {
        Route::middleware('api')->get('/api/test-boom-debug', function () {
            throw new RuntimeException('detalle interno para depurar');
        });

        config(['app.debug' => true]);

        $respuesta = $this->withHeaders(['Accept-Language' => 'es'])->getJson('/api/test-boom-debug');
        $cuerpo = $respuesta->json();

        $this->assertSame(500, $respuesta->getStatusCode());
        $this->assertFalse($cuerpo['success']);
        $this->assertSame(__('api.server_error', [], 'es'), $cuerpo['message']);

        $this->assertArrayHasKey('debug', $cuerpo);
        $this->assertSame(RuntimeException::class, $cuerpo['debug']['exception']['class']);
        $this->assertSame('detalle interno para depurar', $cuerpo['debug']['exception']['message']);
    }

    /**
     * El 429 del throttle: lo provoca cualquiera y se salía del contrato.
     */
    public function test_demasiadas_peticiones(): void
    {
        Route::middleware(['api', 'throttle:1,1'])->get('/api/test-throttle', fn () => response()->json(['ok' => true]));

        // La primera pasa; la segunda ya la corta el limitador.
        $this->getJson('/api/test-throttle');

        foreach (['application/json', '*/*'] as $accept) {
            $respuesta = $this->call('GET', '/api/test-throttle', [], [], [], [
                'HTTP_ACCEPT' => $accept,
                'HTTP_ACCEPT_LANGUAGE' => 'es',
            ]);

            $this->assertSame(429, $respuesta->getStatusCode());
            $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));

            $cuerpo = json_decode((string) $respuesta->getContent(), true);

            $this->assertFalse($cuerpo['success']);
            $this->assertSame(__('api.too_many_requests', [], 'es'), $cuerpo['message']);
            $this->assertArrayNotHasKey('trace', $cuerpo);

            // El cliente necesita saber cuánto esperar: la cabecera de la
            // excepción HTTP tiene que sobrevivir al envelope.
            $this->assertNotNull(
                $respuesta->headers->get('Retry-After'),
                'El 429 ha perdido la cabecera Retry-After al pasar por el envelope.'
            );
        }
    }

    /**
     * Un borrado sigue siendo 204 sin cuerpo: es la única excepción consciente
     * a «todas las respuestas llevan envelope», y está decidida.
     */
    public function test_el_borrado_sigue_siendo_204_sin_cuerpo(): void
    {
        Route::middleware('api')->delete('/api/test-delete', function () {
            return response()->json(null, 204);
        });

        $respuesta = $this->deleteJson('/api/test-delete');

        $respuesta->assertNoContent();
        $this->assertSame('', $respuesta->getContent());
    }
}
