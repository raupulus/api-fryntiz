<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Support\Http\ApiEnvelope;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Las dos puertas de respuesta de la API devuelven lo mismo.
 *
 * `JsonHelper` (estático, para handlers y rutas) y `ApiResponseTrait` (para
 * controladores) se mantienen los dos a propósito, y por eso hace falta algo
 * que impida que se separen: si alguien toca uno y se olvida del otro, la API
 * empieza a responder de dos formas distintas según por dónde salga la
 * petición, que es exactamente lo que había antes de la revisión de 2026-09-02
 * (el envelope estaba copiado a mano en once sitios).
 *
 * Este test compara **cuerpo y código HTTP** método a método.
 */
class ApiResponseParityTest extends TestCase
{
    /**
     * Un controlador de mentira que expone los métodos protegidos del trait.
     */
    private object $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Sólo hace falta una instancia que use el trait: los métodos son
        // `protected`, así que se llaman por reflexión (accesible desde PHP 8.1
        // sin `setAccessible()`). No se define `__call()` aquí porque chocaría
        // con el de `Illuminate\Routing\Controller`.
        $this->controller = new class extends BaseApiController {};
    }

    /**
     * Llama al método del trait saltándose la visibilidad `protected`.
     */
    private function viaTrait(string $method, mixed ...$arguments): JsonResponse
    {
        $reflection = new \ReflectionMethod($this->controller, $method);

        return $reflection->invoke($this->controller, ...$arguments);
    }

    private function assertSameResponse(JsonResponse $helper, JsonResponse $trait, string $case): void
    {
        $this->assertSame(
            $helper->getStatusCode(),
            $trait->getStatusCode(),
            "El código HTTP de «{$case}» no coincide entre JsonHelper y ApiResponseTrait."
        );

        $this->assertSame(
            $helper->getContent(),
            $trait->getContent(),
            "El cuerpo de «{$case}» no coincide entre JsonHelper y ApiResponseTrait."
        );
    }

    public function test_a_correct_response_is_identical(): void
    {
        $this->assertSameResponse(
            JsonHelper::success(['id' => 1], 'Hecho'),
            $this->viaTrait('successResponse', ['id' => 1], 'Hecho'),
            'success'
        );
    }

    public function test_a_correct_response_with_default_values_is_identical(): void
    {
        $this->assertSameResponse(
            JsonHelper::success(),
            $this->viaTrait('successResponse'),
            'success por defecto'
        );
    }

    public function test_a_created_resource_is_identical(): void
    {
        $helper = JsonHelper::created(['id' => 7], 'Creado', 'https://api.raupulus.dev/x');
        $trait = $this->viaTrait('createdResponse', ['id' => 7], 'Creado', 'https://api.raupulus.dev/x');

        $this->assertSameResponse($helper, $trait, 'created');

        $this->assertSame(
            $helper->headers->get('Location'),
            $trait->headers->get('Location'),
            'La cabecera Location de «created» no coincide.'
        );
    }

    public function test_a_deletion_is_identical_and_still_204_without_body(): void
    {
        $helper = JsonHelper::deleted();
        $trait = $this->viaTrait('deletedResponse');

        $this->assertSameResponse($helper, $trait, 'deleted');

        // Decisión del 2026-09-02: el borrado se queda en 204 sin cuerpo, que
        // es lo correcto en REST, aunque sea la única respuesta sin envelope.
        $this->assertSame(204, $helper->getStatusCode());
    }

    public function test_a_paginated_collection_is_identical(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 1], ['id' => 2]], 40, 25, 1);

        $this->assertSameResponse(
            JsonHelper::paginated($paginator),
            $this->viaTrait('paginatedResponse', $paginator),
            'paginated'
        );
    }

    public function test_warnings_are_identical(): void
    {
        $warnings = ['El canal 3 no tiene elemento activo.', 'Corriente negativa.'];

        $this->assertSameResponse(
            JsonHelper::withWarnings(JsonHelper::success(['ok' => true]), $warnings),
            $this->viaTrait('withWarnings', $this->viaTrait('successResponse', ['ok' => true]), $warnings),
            'withWarnings'
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, mixed>}>
     */
    public static function errorsProvider(): array
    {
        return [
            'error genérico' => ['error', 'errorResponse', ['Se ha roto', 400, ['campo' => ['mal']]]],
            'error sin detalle' => ['error', 'errorResponse', ['Se ha roto', 418]],
            'no encontrado' => ['notFound', 'notFoundResponse', ['No está']],
            'no autenticado' => ['unauthorized', 'unauthorizedResponse', ['Sin credenciales']],
            'sin permiso' => ['forbidden', 'forbiddenResponse', ['No puedes']],
            'conflicto' => ['conflict', 'conflictResponse', ['Ya existe']],
            'no encontrado por defecto' => ['notFound', 'notFoundResponse', []],
            'no autenticado por defecto' => ['unauthorized', 'unauthorizedResponse', []],
            'sin permiso por defecto' => ['forbidden', 'forbiddenResponse', []],
            'conflicto por defecto' => ['conflict', 'conflictResponse', []],
        ];
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    #[DataProvider('errorsProvider')]
    public function test_errors_are_identical(string $helperMethod, string $traitMethod, array $arguments): void
    {
        $this->assertSameResponse(
            JsonHelper::{$helperMethod}(...$arguments),
            $this->viaTrait($traitMethod, ...$arguments),
            $helperMethod
        );
    }

    /**
     * Cada método público de `JsonHelper` tiene su gemelo en el trait, o está
     * en la lista de los que a propósito no lo tienen.
     */
    public function test_no_helper_method_is_missing_its_twin_in_the_trait(): void
    {
        // `serverError` sólo la usa el handler de cierre: un controlador no
        // devuelve un 500 a mano, lo provoca. `failed`, `accepted` y `updated`
        // son nombres históricos de la V1 que se conservan para los `render()`
        // de las excepciones.
        $methodsWithoutTwin = ['serverError', 'failed', 'accepted', 'updated'];

        $helperMethods = array_column(
            (new \ReflectionClass(JsonHelper::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
            'name'
        );

        $traitMethods = array_column(
            (new \ReflectionClass(ApiResponseTrait::class))->getMethods(),
            'name'
        );

        foreach ($helperMethods as $method) {
            if (in_array($method, $methodsWithoutTwin, true)) {
                continue;
            }

            $twin = $method === 'withWarnings' ? 'withWarnings' : $method.'Response';

            $this->assertContains(
                $twin,
                $traitMethods,
                "JsonHelper::{$method}() no tiene gemelo «{$twin}» en ApiResponseTrait. ".
                'Si es deliberado, añádelo a la lista $methodsWithoutTwin de este test y explica por qué.'
            );
        }
    }

    public function test_the_debug_block_does_not_exist_without_app_debug(): void
    {
        config(['app.debug' => false]);

        $payload = JsonHelper::success(['x' => 1])->getData(true);

        $this->assertArrayNotHasKey('debug', $payload);
    }

    public function test_the_debug_block_appears_with_app_debug(): void
    {
        config(['app.debug' => true]);

        $payload = JsonHelper::success(['x' => 1])->getData(true);

        $this->assertArrayHasKey('debug', $payload);
        $this->assertArrayHasKey('headers', $payload['debug']);
        $this->assertArrayHasKey('parameters', $payload['debug']);
    }

    public function test_the_debug_block_does_not_leak_sensitive_headers(): void
    {
        config(['app.debug' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer 1|secretodeverdad',
            'Cookie' => 'laravel_session=abcdef',
            'Accept' => 'application/json',
        ])->getJson('/api/v2/platforms');

        $body = $response->getContent();

        $this->assertStringNotContainsString('secretodeverdad', $body);
        $this->assertStringNotContainsString('laravel_session', $body);
        $this->assertStringNotContainsString('authorization', mb_strtolower($body));
    }

    public function test_the_debug_block_redacts_the_password(): void
    {
        config(['app.debug' => true]);

        $response = $this->postJson('/api/v2/auth/tokens', [
            'email' => 'no-existe@raupulus.dev',
            'password' => 'estonodebesalir',
        ]);

        $this->assertStringNotContainsString('estonodebesalir', $response->getContent());
        $this->assertStringContainsString(ApiEnvelope::REDACTED_PLACEHOLDER, $response->getContent());
    }
}
