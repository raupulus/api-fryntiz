<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentStatusEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentSeo;
use App\Models\Platform;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\SeedsProductionContentStatuses;

class ContentTest extends ApiTestCase
{
    use SeedsProductionContentStatuses;

    protected string $apiPrefix = 'api/v2';

    /**
     * Estados con los ids de producción, no los del seeder: con el seeder
     * cambiado, esta clase dio por buena una API que en producción no servía
     * ningún contenido.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedContentStatusesAsProduction();
    }

    #[Test]
    public function can_get_content_by_platform_and_slug(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function content_show_returns_correct_resource_structure(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure([
            'success', 'message',
            'data' => ['id', 'title', 'slug', 'excerpt', 'type', 'published_at', 'created_at'],
        ]);
    }

    #[Test]
    public function content_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson($this->apiUrl('platforms/no-existe/contents/tampoco'));
        $this->assertErrorResponse($response, 404);
    }

    #[Test]
    public function can_get_content_pages(): void
    {
        $content = Content::factory()->create([
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);
        $response = $this->getJson($this->apiUrl("platforms/{$content->platform->slug}/contents/{$content->slug}/pages"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    #[Test]
    public function pages_returns_404_for_nonexistent_content(): void
    {
        $response = $this->getJson($this->apiUrl('platforms/no-existe/contents/slug-no-existe/pages'));
        $this->assertErrorResponse($response, 404);
    }

    #[Test]
    public function index_does_not_error_with_several_typed_contents(): void
    {
        // `type`/`status` son relaciones BelongsTo que ContentResource lee sin
        // `whenLoaded`. Si el índice no las precarga, acceder a ellas sobre 2+
        // filas dispara una LazyLoadingViolationException fuera de producción
        // en vez de responder 200 (bug real, arreglado 2026-08-30).
        $platform = Platform::factory()->create();
        Content::factory()->count(3)->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents"));
        $this->assertPaginatedResponse($response);
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function can_get_related_content(): void
    {
        $content = Content::factory()->create([
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);
        $response = $this->getJson($this->apiUrl("platforms/{$content->platform->slug}/contents/{$content->slug}/related"));
        $this->assertSuccessResponse($response);
        $response->assertJsonStructure(['data']);
    }

    // ─── Una página concreta (GET /platforms/{p}/contents/{c}/pages/{order}) ───

    #[Test]
    public function can_get_a_content_page_by_its_order(): void
    {
        [$platform, $content] = $this->makePublishedContent();

        ContentPage::create([
            'content_id' => $content->id,
            'title' => 'Segunda parte',
            'slug' => 'segunda-parte',
            'content' => 'Cuerpo de la página.',
            'order' => 2,
        ]);

        $response = $this->getJson(
            $this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}/pages/2")
        );

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.title', 'Segunda parte');
    }

    #[Test]
    public function an_order_with_no_page_is_a_404(): void
    {
        [$platform, $content] = $this->makePublishedContent();

        $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}/pages/99"))
            ->assertStatus(404);
    }

    #[Test]
    public function a_page_of_an_unknown_content_is_a_404(): void
    {
        $platform = Platform::factory()->create();

        $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/no-existe/pages/1"))
            ->assertStatus(404);
    }

    // ─── Formato de las páginas (?format=) ───

    /**
     * Página publicada escrita en Editor.js, guardada como la guarda el panel.
     *
     * @return array{0: Platform, 1: Content}
     */
    private function makeEditorJsPage(): array
    {
        (new ContentAvailablePageRawSeeder)->run();

        [$platform, $content] = $this->makePublishedContent();

        $page = ContentPage::create([
            'content_id' => $content->id,
            'title' => 'Primera parte',
            'slug' => 'primera-parte',
            'order' => 1,
        ]);

        app(ContentPageFormatService::class)->save($page, ContentPageFormatEnum::EditorJs, (string) json_encode([
            'time' => 1,
            'version' => '2.29.0',
            'blocks' => [['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola <b>mundo</b>']]],
        ]));

        return [$platform, $content];
    }

    #[Test]
    public function without_format_a_page_comes_in_its_own_format(): void
    {
        [$platform, $content] = $this->makeEditorJsPage();

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}/pages/1"));

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.format', 'editorjs')
            ->assertJsonPath('data.source_format', 'editorjs')
            ->assertJsonPath('data.body.blocks.0.data.text', 'Hola <b>mundo</b>');
    }

    #[Test]
    public function a_page_can_be_asked_for_in_html_or_markdown(): void
    {
        [$platform, $content] = $this->makeEditorJsPage();
        $url = "platforms/{$platform->slug}/contents/{$content->slug}/pages";

        $html = $this->getJson($this->apiUrl("{$url}?format=html"));
        $html->assertJsonPath('data.0.format', 'html')->assertJsonPath('data.0.source_format', 'editorjs');
        $this->assertStringContainsString('Hola <b>mundo</b>', (string) $html->json('data.0.body'));

        $this->getJson($this->apiUrl("{$url}/1?format=markdown"))
            ->assertJsonPath('data.format', 'markdown')
            ->assertJsonPath('data.body', "Hola **mundo**\n");
    }

    #[Test]
    public function an_unknown_format_is_a_validation_error(): void
    {
        [$platform, $content] = $this->makeEditorJsPage();

        $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}/pages?format=pdf"))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.format.0', 'El formato tiene que ser editorjs, markdown o html.');
    }

    /**
     * Contenido publicado y visible, que es la condición para que la API lo
     * sirva.
     *
     * @return array{0: Platform, 1: Content}
     */
    private function makePublishedContent(): array
    {
        $platform = Platform::factory()->create();

        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'status_id' => ContentStatusEnum::Published->value,
            'published_at' => now()->subDay(),
        ]);

        return [$platform, $content];
    }

    // ─── Metadatos SEO en la respuesta ───

    /**
     * `seo_title` y `seo_description` salían SIEMPRE null: el resource leía
     * `$content->seo_title`, que no existe ni como columna ni como accessor —el
     * SEO vive en la relación `seo`—. Las webs que consumen la API los usan
     * para construir sus meta tags, así que devolvían páginas sin SEO.
     */
    #[Test]
    public function content_exposes_the_seo_metadata_of_its_relation(): void
    {
        [$platform, $content] = $this->makePublishedContent();

        ContentSeo::create([
            'content_id' => $content->id,
            'og_title' => 'Título para compartir',
            'description' => 'Descripción para buscadores',
        ]);

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));

        $response->assertOk();
        $response->assertJsonPath('data.seo_title', 'Título para compartir');
        $response->assertJsonPath('data.seo_description', 'Descripción para buscadores');
    }

    #[Test]
    public function seo_metadata_falls_back_to_the_content_itself(): void
    {
        // Sin fila de SEO se devuelve el título y el extracto del contenido:
        // es mejor que un null que obliga a la web a inventarse el meta.
        [$platform, $content] = $this->makePublishedContent();

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));

        $response->assertOk();
        $response->assertJsonPath('data.seo_title', $content->title);
        $this->assertNotNull($response->json('data.seo_title'));
    }

    #[Test]
    public function content_reports_its_real_view_count_and_page_count(): void
    {
        // `views_count` salía 0 SIEMPRE y `pages_count` no aparecía nunca:
        // ninguna consulta los agregaba, aunque las visitas sí se contaban en
        // `content_daily_views` (las escribe ProcessContentViewJob).
        [$platform, $content] = $this->makePublishedContent();

        ContentPage::create([
            'content_id' => $content->id,
            'title' => 'Una página',
            'slug' => 'una-pagina',
            'content' => 'Cuerpo.',
            'order' => 1,
        ]);

        DB::table('content_daily_views')->insert([
            ['content_id' => $content->id, 'date' => now()->subDay()->toDateString(), 'views' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['content_id' => $content->id, 'date' => now()->toDateString(), 'views' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));

        $response->assertOk();
        $response->assertJsonPath('data.views_count', 12);
        $response->assertJsonPath('data.pages_count', 1);
    }

    #[Test]
    public function a_content_without_views_reports_zero_and_not_null(): void
    {
        [$platform, $content] = $this->makePublishedContent();

        $response = $this->getJson($this->apiUrl("platforms/{$platform->slug}/contents/{$content->slug}"));

        $response->assertOk();
        $response->assertJsonPath('data.views_count', 0);
    }
}
