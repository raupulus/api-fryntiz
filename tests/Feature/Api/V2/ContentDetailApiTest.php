<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Services\Content\ContentApiService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\BuildsApiContents;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * El detalle de un contenido y sus partes por separado (P5/F1, F2, F3, E7 y
 * P7 de la auditoría de contenidos; F9 del plan del 2026-09-24).
 */
class ContentDetailApiTest extends ApiTestCase
{
    use BuildsApiContents;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    /**
     * Lo que cada `include` añade al detalle.
     */
    private const PARTS = ['seo', 'metadata', 'taxonomies', 'technologies', 'contributors', 'galleries', 'files', 'related'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareApiContents();
    }

    // ── Detalle ─────────────────────────────────────────────────────────────

    #[Test]
    public function the_detail_without_parameters_brings_the_first_page_with_its_text_and_the_index_without_text(): void
    {
        ['content' => $content] = $this->richContent();

        $data = $this->getJson($this->contentUrl($content))->assertOk()->json('data');

        $this->assertSame(['Introducción', 'Montaje', 'Resultados'], array_column($data['pages'], 'title'));

        foreach ($data['pages'] as $page) {
            $this->assertSame(['id', 'order', 'title', 'slug', 'format'], array_keys($page), 'El índice no lleva texto.');
            $this->assertSame('html', $page['format']);
        }

        $this->assertSame(1, $data['first_page']['order']);
        $this->assertStringContainsString('Texto de la primera página', $data['first_page']['body']);
        $this->assertStringNotContainsString('segunda página', (string) json_encode($data));

        foreach (self::PARTS as $part) {
            $this->assertArrayNotHasKey($part, $data, "Sin include no va «{$part}».");
        }
    }

    #[Test]
    public function the_detail_carries_the_compact_type_and_status_and_the_switches_of_the_contract(): void
    {
        $content = $this->published([
            'is_visible_on_home' => true,
            'is_visible_on_rss' => false,
            'is_comment_enabled' => true,
            'is_comment_anonymous' => false,
            'is_copyright_valid' => null,
        ]);

        $data = $this->getJson($this->contentUrl($content))->assertOk()->json('data');

        $this->assertSame(['id', 'slug', 'name', 'plural_name'], array_keys($data['type']));
        $this->assertSame('blog', $data['type']['slug']);
        $this->assertSame(['id', 'slug', 'name'], array_keys($data['status']));
        $this->assertSame(
            ['home', 'menu', 'footer', 'sidebar', 'search', 'archive', 'rss', 'sitemap', 'sitemap_news'],
            array_keys($data['visibility']),
        );
        $this->assertTrue($data['visibility']['home']);
        $this->assertFalse($data['visibility']['rss']);
        $this->assertSame(['enabled' => true, 'anonymous' => false], $data['comments']);
        $this->assertNull($data['copyright_valid'], 'Sin comprobar no es «no».');
        $this->assertNull($data['first_page'], 'Sin páginas, no hay primera página.');
        $this->assertSame([], $data['pages']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function parts(): array
    {
        return array_combine(self::PARTS, array_map(fn (string $part): array => [$part], self::PARTS));
    }

    #[Test]
    #[DataProvider('parts')]
    public function each_include_adds_only_its_part(string $part): void
    {
        ['content' => $content] = $this->richContent();

        $data = $this->getJson($this->contentUrl($content, "?include={$part}"))->assertOk()->json('data');

        $this->assertArrayHasKey($part, $data);
        $this->assertNotEmpty($data[$part], "«{$part}» tiene datos en este contenido.");

        foreach (array_diff(self::PARTS, [$part]) as $other) {
            $this->assertArrayNotHasKey($other, $data, "Con include={$part} no va «{$other}».");
        }

        $this->assertArrayNotHasKey('body', $data['pages'][0], 'Sin include=pages el índice sigue sin texto.');
    }

    #[Test]
    public function include_all_brings_everything_and_every_page_with_its_text(): void
    {
        ['content' => $content] = $this->richContent();

        $data = $this->getJson($this->contentUrl($content, '?include=all'))->assertOk()->json('data');

        foreach (self::PARTS as $part) {
            $this->assertArrayHasKey($part, $data);
        }

        $this->assertCount(3, $data['pages']);
        $this->assertStringContainsString('Texto de la tercera página', $data['pages'][2]['body']);
    }

    #[Test]
    public function an_unknown_include_is_ignored(): void
    {
        ['content' => $content] = $this->richContent();

        $data = $this->getJson($this->contentUrl($content, '?include=nada,seo'))->assertOk()->json('data');

        $this->assertArrayHasKey('seo', $data);
        $this->assertArrayNotHasKey('nada', $data);
    }

    #[Test]
    public function the_parts_leave_out_deleted_pivots_and_unused_files(): void
    {
        ['content' => $content, 'file' => $file, 'unused' => $unused] = $this->richContent();

        $data = $this->getJson($this->contentUrl($content, '?include=taxonomies,files,contributors,galleries,technologies'))->assertOk()->json('data');

        $this->assertSame(['Meteorología'], array_column($data['taxonomies']['categories'], 'name'));
        $this->assertTrue($data['taxonomies']['categories'][0]['is_main']);
        $this->assertSame([['Sensores', 'meteorologia']], array_map(fn (array $row): array => [$row['name'], $row['parent']], $data['taxonomies']['subcategories']));
        $this->assertSame(['Raspberry'], array_column($data['taxonomies']['tags'], 'name'));

        $this->assertSame([$file->id], array_column($data['files'], 'id'));
        $this->assertNotContains($unused->id, array_column($data['files'], 'id'));
        $this->assertTrue($data['files'][0]['is_image']);

        $this->assertSame([['name', 'nick', 'image']], array_map('array_keys', $data['contributors']), 'Colaboradores sólo con nombre, apodo e imagen.');
        $this->assertSame(['PHP'], array_column($data['technologies'], 'name'));

        // Las fotos, en su orden (la factoría las crea al revés).
        $this->assertSame([1, 2], array_column($data['galleries'][0]['images'], 'order'));
    }

    #[Test]
    public function a_content_slug_cannot_be_highlights_because_it_is_an_api_route(): void
    {
        $this->assertContains('highlights', ContentApiService::RESERVED_SLUGS);

        // Aunque alguien lo tuviera (datos viejos), la ruta es la de destacados.
        $this->published(['slug' => 'highlights', 'title' => 'Se llama highlights']);

        $response = $this->getJson("/api/v2/platforms/{$this->platform->slug}/contents/highlights")->assertOk();

        $this->assertSame(['featured', 'latest', 'trend'], array_keys($response->json('data')));
    }

    // ── Páginas ─────────────────────────────────────────────────────────────

    #[Test]
    public function pages_can_be_asked_for_from_a_number_and_with_a_limit(): void
    {
        ['content' => $content] = $this->richContent();

        $pages = $this->getJson($this->contentUrl($content, '/pages?from=2&limit=1'))->assertOk()->json('data');
        $this->assertSame([2], array_column($pages, 'order'));
        $this->assertStringContainsString('segunda página', $pages[0]['body']);

        $this->assertSame([2, 3], array_column($this->getJson($this->contentUrl($content, '/pages?from=2'))->json('data'), 'order'));
        $this->assertSame([1, 2], array_column($this->getJson($this->contentUrl($content, '/pages?limit=2'))->json('data'), 'order'));

        $this->getJson($this->contentUrl($content, '/pages?from=0'))->assertStatus(422)->assertJsonValidationErrors('from');
        $this->getJson($this->contentUrl($content, '/pages?limit=101'))->assertStatus(422)->assertJsonValidationErrors('limit');
    }

    #[Test]
    public function a_page_can_be_asked_for_by_its_slug(): void
    {
        ['content' => $content] = $this->richContent();

        $this->getJson($this->contentUrl($content, '/pages/slug/montaje?format=markdown'))
            ->assertOk()
            ->assertJsonPath('data.order', 2)
            ->assertJsonPath('data.format', 'markdown');

        $this->getJson($this->contentUrl($content, '/pages/slug/no-existe'))->assertNotFound();
    }

    #[Test]
    public function each_part_has_its_own_route(): void
    {
        ['content' => $content, 'gallery' => $gallery] = $this->richContent();

        $this->getJson($this->contentUrl($content, '/seo'))->assertOk()->assertJsonPath('data.og_title', 'Para compartir');
        $this->getJson($this->contentUrl($content, '/galleries'))->assertOk()->assertJsonPath('data.0.id', $gallery->id);
        $this->assertCount(1, $this->getJson($this->contentUrl($content, '/files'))->assertOk()->json('data'));

        $draft = $this->published(['status_id' => 1, 'slug' => 'borrador']);
        $this->getJson($this->contentUrl($draft, '/seo'))->assertNotFound();
    }

    // ── Relacionados ────────────────────────────────────────────────────────

    #[Test]
    public function related_are_the_chosen_ones_first_in_order_and_then_the_latest_of_the_same_type(): void
    {
        $content = $this->published(['slug' => 'principal']);
        $second = $this->published(['title' => 'Elegido segundo', 'published_at' => now()->subDays(20)]);
        $first = $this->published(['title' => 'Elegido primero', 'published_at' => now()->subDays(30)]);
        $gone = $this->published(['title' => 'Desvinculado']);
        $recent = $this->published(['title' => 'Reciente', 'published_at' => now()->subDay()]);
        $older = $this->published(['title' => 'Más viejo', 'published_at' => now()->subDays(2)]);
        $this->published(['title' => 'De otro tipo', 'type_id' => $this->typeId('project'), 'published_at' => now()]);
        $this->published(['title' => 'Borrador', 'status_id' => 1, 'published_at' => now()]);

        // El orden es el de vinculación, no el de publicación.
        $this->linkRelated($content, $first);
        $this->linkRelated($content, $gone, deleted: true);
        $this->linkRelated($content, $second);

        $titles = array_column($this->getJson($this->contentUrl($content, '/related?limit=4'))->assertOk()->json('data'), 'title');

        $this->assertSame(['Elegido primero', 'Elegido segundo', 'Reciente', 'Más viejo'], $titles);

        $item = $this->getJson($this->contentUrl($content, '/related?limit=1'))->json('data.0');
        $this->assertSame(['id', 'title', 'slug', 'excerpt', 'image', 'type', 'is_featured', 'published_at'], array_keys($item));
    }

    // ── Listado y filtros ───────────────────────────────────────────────────

    #[Test]
    public function the_list_filters_by_category_tag_technology_and_text(): void
    {
        ['content' => $content] = $this->richContent();
        $this->published(['title' => 'Otro 100% distinto', 'excerpt' => 'Nada que ver']);

        $titles = fn (string $query): array => array_column($this->getJson("/api/v2/platforms/{$this->platform->slug}/contents?{$query}")->assertOk()->json('data'), 'title');

        $this->assertSame([$content->title], $titles('category=meteorologia'));
        $this->assertSame([$content->title], $titles('category=sensores'));
        $this->assertSame([], $titles('category=borrada'), 'Un pivote borrado no cuenta.');
        $this->assertSame([$content->title], $titles('tag=raspberry'));
        $this->assertSame([], $titles('tag=vieja'));
        $this->assertSame([$content->title], $titles('technology=php'));
        $this->assertSame([$content->title], $titles('q=CASERA'), 'Sin distinguir mayúsculas, en el extracto.');
        $this->assertSame(['Otro 100% distinto'], $titles('q='.urlencode('100%')), 'El % se busca tal cual.');
        $this->assertSame([], $titles('q=_'), 'Un _ no es un comodín.');
    }

    #[Test]
    public function the_platform_tags_come_with_how_many_published_contents_use_them(): void
    {
        $this->richContent();
        $this->tag('Sin usar');

        $tags = collect($this->getJson("/api/v2/platforms/{$this->platform->slug}/tags")->assertOk()->json('data'))->keyBy('slug');

        $this->assertSame(1, $tags['raspberry']['contents_count']);
        $this->assertSame(0, $tags['vieja']['contents_count'], 'Un pivote borrado no cuenta.');
        $this->assertSame(0, $tags['sin-usar']['contents_count']);
        $this->getJson('/api/v2/platforms/no-existe/tags')->assertNotFound();
    }

    // ── Destacados, últimos y tendencia ─────────────────────────────────────

    #[Test]
    public function highlights_group_featured_latest_and_trend_by_type(): void
    {
        $featured = $this->published(['title' => 'Destacado', 'is_featured' => true]);
        $this->published(['title' => 'Último', 'published_at' => now()->subHour()]);
        $viewed = $this->published(['title' => 'Muy visto', 'published_at' => now()->subDays(20)]);
        $old = $this->published(['title' => 'Visto hace tiempo', 'published_at' => now()->subDays(25)]);
        $project = $this->published(['title' => 'Proyecto', 'type_id' => $this->typeId('project')]);

        DB::table('content_daily_views')->insert([
            ['content_id' => $viewed->id, 'date' => now()->subDay()->toDateString(), 'views' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['content_id' => $old->id, 'date' => now()->subDays(10)->toDateString(), 'views' => 500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $data = $this->getJson("/api/v2/platforms/{$this->platform->slug}/contents/highlights?limit=2")->assertOk()->json('data');

        $this->assertSame(['blog', 'project'], array_keys($data['latest']));
        $this->assertSame([$featured->title], array_column($data['featured']['blog'], 'title'));
        $this->assertNotContains($featured->title, array_column($data['latest']['blog'], 'title'), 'Los últimos no repiten los destacados.');
        $this->assertSame('Último', $data['latest']['blog'][0]['title']);
        $this->assertSame('Muy visto', $data['trend']['blog'][0]['title'], 'Tendencia: visitas de los últimos 3 días, no de siempre.');
        $this->assertCount(2, $data['trend']['blog']);
        $this->assertSame([$project->title], array_column($data['latest']['project'], 'title'));

        $only = $this->getJson("/api/v2/platforms/{$this->platform->slug}/contents/highlights?type=featured")->assertOk()->json('data');
        $this->assertSame(['featured'], array_keys($only));

        $this->getJson("/api/v2/platforms/{$this->platform->slug}/contents/highlights?type=otro")->assertStatus(422);
        $this->getJson('/api/v2/platforms/no-existe/contents/highlights')->assertNotFound();
    }

    // ── Consultas por ruta ──────────────────────────────────────────────────

    /**
     * Máximo de consultas de cada ruta montando la respuesta (sin caché), con
     * el contenido completo. Si sube, algo ha vuelto a cargar de más.
     *
     * @return array<string, array{string, int}>
     */
    public static function queryBudgets(): array
    {
        return [
            'detalle' => ['', 17],
            'detalle con todo' => ['?include=all', 31],
            'páginas' => ['/pages', 5],
            'una página' => ['/pages/2', 5],
            'página por slug' => ['/pages/slug/montaje', 5],
            'relacionados' => ['/related', 7],
            'seo' => ['/seo', 2],
            'galerías' => ['/galleries', 5],
            'ficheros' => ['/files', 3],
        ];
    }

    #[Test]
    #[DataProvider('queryBudgets')]
    public function each_route_stays_within_its_query_budget(string $suffix, int $budget): void
    {
        ['content' => $content] = $this->richContent();

        $queries = $this->queriesOf(fn () => $this->getJson($this->contentUrl($content, $suffix))->assertOk());

        $this->assertLessThanOrEqual($budget, count($queries), implode("\n", $queries));
    }

    #[Test]
    public function the_detail_reads_the_text_of_the_first_page_only(): void
    {
        ['content' => $content] = $this->richContent();

        $queries = $this->queriesOf(fn () => $this->getJson($this->contentUrl($content))->assertOk());

        $withText = array_filter($queries, fn (string $sql): bool => str_contains($sql, 'from "content_pages"') && str_starts_with($sql, 'select *'));

        $this->assertCount(1, $withText, implode("\n", $queries));
        $this->assertStringContainsString('limit 1', (string) array_values($withText)[0]);
    }

    #[Test]
    public function the_list_and_the_platform_stay_within_their_query_budget(): void
    {
        $this->richContent();
        $this->published(['title' => 'Otro', 'is_featured' => true]);

        $slug = $this->platform->slug;
        $budgets = [
            "/api/v2/platforms/{$slug}/contents" => 10,
            "/api/v2/platforms/{$slug}/contents/highlights" => 8,
            "/api/v2/platforms/{$slug}" => 8,
            "/api/v2/platforms/{$slug}/tags" => 2,
        ];

        foreach ($budgets as $url => $budget) {
            $queries = $this->queriesOf(fn () => $this->getJson($url)->assertOk());
            $this->assertLessThanOrEqual($budget, count($queries), $url."\n".implode("\n", $queries));
        }
    }

    /**
     * Las consultas de una petición, con la caché vacía.
     *
     * @return list<string>
     */
    private function queriesOf(callable $request): array
    {
        cache()->flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request();

        $queries = array_map(fn (array $query): string => $query['query'], DB::getQueryLog());
        DB::disableQueryLog();

        // Las visitas se suman después de responder: no son de la respuesta.
        return array_values(array_filter($queries, fn (string $sql): bool => ! str_contains($sql, 'content_daily_views" ("content_id"')));
    }
}
