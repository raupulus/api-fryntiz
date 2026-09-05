<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Platform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Un filtro mal formado responde 422, no 500.
 *
 * `CollectionQuery` siempre ha tenido lista blanca de columnas, así que nunca
 * se pudo filtrar por una columna arbitraria. Lo que no se comprobaba era el
 * **valor**, y PostgreSQL es estricto: un texto donde espera un `timestamp` no
 * devuelve cero filas, lanza `SQLSTATE 22007`.
 *
 * O sea un 500 que provocaba cualquiera, sin autenticar y sin límite de
 * peticiones, en todas las colecciones públicas de la API (auditoría AR-E01,
 * reproducido con `curl` antes de arreglarlo).
 */
class CollectionFilterTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * @return array<string, array{0: string}>
     */
    public static function filtrosInvalidosProvider(): array
    {
        return [
            'fecha que no es fecha' => ['/api/v2/platforms?created_at=abc'],
            'rango con fecha inválida' => ['/api/v2/platforms?created_at[gte]=zzz'],
            'rango con una lista dentro' => ['/api/v2/platforms?created_at[gte][]=1'],
            'lista con un elemento inválido' => ['/api/v2/platforms?created_at=2020-01-01,abc'],
            'otra colección pública' => ['/api/v2/curriculum?created_at=basura'],
            'serie temporal de sensores' => ['/api/v2/weather-stations/1/temperatures?created_at=x'],
            'per_page que no es número' => ['/api/v2/platforms?per_page=abc'],
            'página negativa' => ['/api/v2/platforms?page=-1'],
        ];
    }

    #[Test]
    #[DataProvider('filtrosInvalidosProvider')]
    public function un_filtro_invalido_responde_422_con_el_envelope(string $url): void
    {
        $respuesta = $this->getJson($url);

        $respuesta->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function filtrosValidosProvider(): array
    {
        return [
            'sin filtros' => ['/api/v2/platforms'],
            'rango de fechas' => ['/api/v2/platforms?created_at[gte]=2020-01-01'],
            'alias from/to' => ['/api/v2/platforms?from=2020-01-01&to=2030-01-01'],
            'igualdad de texto' => ['/api/v2/platforms?slug=raupulus'],
            'lista de textos' => ['/api/v2/platforms?slug=a,b,c'],
            'orden' => ['/api/v2/platforms?sort=-created_at'],
            'paginación' => ['/api/v2/platforms?per_page=50&page=1'],
            // Un campo de formulario en blanco no es un error: las webs los
            // mandan continuamente.
            'filtro vacío' => ['/api/v2/platforms?created_at='],
            // Decisión de siempre: lo que no está en la lista blanca se ignora,
            // ni filtra ni revienta.
            'campo desconocido' => ['/api/v2/platforms?pepito=1'],
            'operador desconocido' => ['/api/v2/platforms?created_at[loquesea]=1'],
            'orden desconocido' => ['/api/v2/platforms?sort=-noexiste'],
        ];
    }

    #[Test]
    #[DataProvider('filtrosValidosProvider')]
    public function un_filtro_valido_sigue_funcionando(string $url): void
    {
        $this->getJson($url)->assertOk()->assertJson(['success' => true]);
    }

    #[Test]
    public function el_error_dice_que_campo_falla(): void
    {
        $this->getJson('/api/v2/platforms?created_at=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['created_at']);
    }

    #[Test]
    public function el_tipo_se_deduce_del_nombre_de_la_columna(): void
    {
        // `is_featured` es booleano por el prefijo `is_`, `type_id` entero por
        // el sufijo `_id`, `published_at` fecha por `_at`. Sin la convención
        // habría que repetir el tipo en los once sitios donde se construye un
        // CollectionQuery.
        $plataforma = Platform::factory()->create();
        $url = "/api/v2/platforms/{$plataforma->slug}/contents";

        $this->getJson($url.'?is_featured=quiza')->assertStatus(422);
        $this->getJson($url.'?type_id=abc')->assertStatus(422);
        $this->getJson($url.'?published_at=ayer-por-la-tarde')->assertStatus(422);

        // Y los mismos campos con un valor del tipo correcto pasan.
        $this->getJson($url.'?is_featured=1')->assertOk();
        $this->getJson($url.'?type_id=3')->assertOk();
        $this->getJson($url.'?published_at=2026-01-01')->assertOk();
    }
}
