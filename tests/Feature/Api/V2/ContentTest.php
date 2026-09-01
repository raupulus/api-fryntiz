<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class ContentTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function can_get_content_by_platform_and_slug(): void
    {
        $platform = Platform::factory()->create();
        $content = Content::factory()->create([
            'platform_id' => $platform->id,
            'is_active' => true,
            'status_id' => 2,
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
            'status_id' => 2,
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
            'status_id' => 2,
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
            'status_id' => 2,
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
            'status_id' => 2,
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
            'status_id' => 2,
            'published_at' => now()->subDay(),
        ]);

        return [$platform, $content];
    }
}
