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

    private function assertMismaRespuesta(JsonResponse $helper, JsonResponse $trait, string $caso): void
    {
        $this->assertSame(
            $helper->getStatusCode(),
            $trait->getStatusCode(),
            "El código HTTP de «{$caso}» no coincide entre JsonHelper y ApiResponseTrait."
        );

        $this->assertSame(
            $helper->getContent(),
            $trait->getContent(),
            "El cuerpo de «{$caso}» no coincide entre JsonHelper y ApiResponseTrait."
        );
    }

    public function test_respuesta_correcta_es_identica(): void
    {
        $this->assertMismaRespuesta(
            JsonHelper::success(['id' => 1], 'Hecho'),
            $this->viaTrait('successResponse', ['id' => 1], 'Hecho'),
            'success'
        );
    }

    public function test_respuesta_correcta_con_valores_por_defecto_es_identica(): void
    {
        $this->assertMismaRespuesta(
            JsonHelper::success(),
            $this->viaTrait('successResponse'),
            'success por defecto'
        );
    }

    public function test_recurso_creado_es_identico(): void
    {
        $helper = JsonHelper::created(['id' => 7], 'Creado', 'https://api.raupulus.dev/x');
        $trait = $this->viaTrait('createdResponse', ['id' => 7], 'Creado', 'https://api.raupulus.dev/x');

        $this->assertMismaRespuesta($helper, $trait, 'created');

        $this->assertSame(
            $helper->headers->get('Location'),
            $trait->headers->get('Location'),
            'La cabecera Location de «created» no coincide.'
        );
    }

    public function test_borrado_es_identico_y_sigue_siendo_204_sin_cuerpo(): void
    {
        $helper = JsonHelper::deleted();
        $trait = $this->viaTrait('deletedResponse');

        $this->assertMismaRespuesta($helper, $trait, 'deleted');

        // Decisión del 2026-09-02: el borrado se queda en 204 sin cuerpo, que
        // es lo correcto en REST, aunque sea la única respuesta sin envelope.
        $this->assertSame(204, $helper->getStatusCode());
    }

    public function test_coleccion_paginada_es_identica(): void
    {
        $paginador = new LengthAwarePaginator([['id' => 1], ['id' => 2]], 40, 25, 1);

        $this->assertMismaRespuesta(
            JsonHelper::paginated($paginador),
            $this->viaTrait('paginatedResponse', $paginador),
            'paginated'
        );
    }

    public function test_avisos_son_identicos(): void
    {
        $avisos = ['El canal 3 no tiene elemento activo.', 'Corriente negativa.'];

        $this->assertMismaRespuesta(
            JsonHelper::withWarnings(JsonHelper::success(['ok' => true]), $avisos),
            $this->viaTrait('withWarnings', $this->viaTrait('successResponse', ['ok' => true]), $avisos),
            'withWarnings'
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, mixed>}>
     */
    public static function erroresProvider(): array
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
    #[DataProvider('erroresProvider')]
    public function test_los_errores_son_identicos(string $helperMethod, string $traitMethod, array $arguments): void
    {
        $this->assertMismaRespuesta(
            JsonHelper::{$helperMethod}(...$arguments),
            $this->viaTrait($traitMethod, ...$arguments),
            $helperMethod
        );
    }

    /**
     * Cada método público de `JsonHelper` tiene su gemelo en el trait, o está
     * en la lista de los que a propósito no lo tienen.
     */
    public function test_no_hay_metodos_del_helper_sin_gemelo_en_el_trait(): void
    {
        // `serverError` sólo la usa el handler de cierre: un controlador no
        // devuelve un 500 a mano, lo provoca. `failed`, `accepted` y `updated`
        // son nombres históricos de la V1 que se conservan para los `render()`
        // de las excepciones.
        $sinGemelo = ['serverError', 'failed', 'accepted', 'updated'];

        $metodosHelper = array_column(
            (new \ReflectionClass(JsonHelper::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
            'name'
        );

        $metodosTrait = array_column(
            (new \ReflectionClass(ApiResponseTrait::class))->getMethods(),
            'name'
        );

        foreach ($metodosHelper as $metodo) {
            if (in_array($metodo, $sinGemelo, true)) {
                continue;
            }

            $gemelo = $metodo === 'withWarnings' ? 'withWarnings' : $metodo.'Response';

            $this->assertContains(
                $gemelo,
                $metodosTrait,
                "JsonHelper::{$metodo}() no tiene gemelo «{$gemelo}» en ApiResponseTrait. ".
                'Si es deliberado, añádelo a la lista $sinGemelo de este test y explica por qué.'
            );
        }
    }

    public function test_el_bloque_debug_no_existe_sin_app_debug(): void
    {
        config(['app.debug' => false]);

        $payload = JsonHelper::success(['x' => 1])->getData(true);

        $this->assertArrayNotHasKey('debug', $payload);
    }

    public function test_el_bloque_debug_aparece_con_app_debug(): void
    {
        config(['app.debug' => true]);

        $payload = JsonHelper::success(['x' => 1])->getData(true);

        $this->assertArrayHasKey('debug', $payload);
        $this->assertArrayHasKey('headers', $payload['debug']);
        $this->assertArrayHasKey('parameters', $payload['debug']);
    }

    public function test_el_bloque_debug_no_filtra_cabeceras_sensibles(): void
    {
        config(['app.debug' => true]);

        $respuesta = $this->withHeaders([
            'Authorization' => 'Bearer 1|secretodeverdad',
            'Cookie' => 'laravel_session=abcdef',
            'Accept' => 'application/json',
        ])->getJson('/api/v2/platforms');

        $cuerpo = $respuesta->getContent();

        $this->assertStringNotContainsString('secretodeverdad', $cuerpo);
        $this->assertStringNotContainsString('laravel_session', $cuerpo);
        $this->assertStringNotContainsString('authorization', mb_strtolower($cuerpo));
    }

    public function test_el_bloque_debug_tapa_la_contrasena(): void
    {
        config(['app.debug' => true]);

        $respuesta = $this->postJson('/api/v2/auth/tokens', [
            'email' => 'no-existe@raupulus.dev',
            'password' => 'estonodebesalir',
        ]);

        $this->assertStringNotContainsString('estonodebesalir', $respuesta->getContent());
        $this->assertStringContainsString(ApiEnvelope::REDACTED_PLACEHOLDER, $respuesta->getContent());
    }
}
