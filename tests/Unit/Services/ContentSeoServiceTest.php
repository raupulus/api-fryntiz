<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Content\Content;
use App\Models\Content\ContentSeo;
use App\Models\Platform;
use App\Services\Content\ContentSeoService;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Este servicio no tenía NINGUNA cobertura, ni directa ni indirecta (TES-02).
 *
 * Lo que hace es un `updateOrCreate` sobre `content_id`, y ahí está justo lo
 * que hay que fijar: que guardar dos veces el SEO de un contenido actualice la
 * fila en lugar de crear una segunda, porque la relación es uno a uno y dos
 * filas dejarían el contenido con metadatos duplicados y ninguno canónico.
 */
class ContentSeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentSeoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();

        $this->service = app(ContentSeoService::class);
    }

    private function makeContent(): Content
    {
        return Content::factory()->create([
            'platform_id' => Platform::factory()->create()->id,
        ]);
    }

    #[Test]
    public function creates_the_seo_for_content_that_did_not_have_it(): void
    {
        $content = $this->makeContent();

        $seo = $this->service->upsert($content, [
            'description' => 'Una descripción',
            'keywords' => 'una, otra',
        ]);

        $this->assertInstanceOf(ContentSeo::class, $seo);
        $this->assertSame($content->id, $seo->content_id);
        $this->assertSame('Una descripción', $seo->description);
    }

    #[Test]
    public function saving_twice_updates_and_does_not_duplicate(): void
    {
        $content = $this->makeContent();

        $first = $this->service->upsert($content, ['description' => 'Primera']);
        $second = $this->service->upsert($content, ['description' => 'Segunda']);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Segunda', $second->fresh()->description);
        $this->assertSame(1, ContentSeo::where('content_id', $content->id)->count());
    }

    #[Test]
    public function each_content_has_its_own_seo_row(): void
    {
        $first = $this->makeContent();
        $second = $this->makeContent();

        $this->service->upsert($first, ['description' => 'La del primero']);
        $this->service->upsert($second, ['description' => 'La del segundo']);

        $this->assertSame(2, ContentSeo::query()->count());
        $this->assertSame('La del primero', ContentSeo::where('content_id', $first->id)->first()->description);
    }

    #[Test]
    public function a_partial_upsert_does_not_clear_what_is_not_sent(): void
    {
        // Editar sólo la descripción desde el panel no debe vaciar las
        // keywords que ya estaban puestas.
        $content = $this->makeContent();

        $this->service->upsert($content, [
            'description' => 'Original',
            'keywords' => 'palabra, clave',
        ]);

        $this->service->upsert($content, ['description' => 'Cambiada']);

        $seo = ContentSeo::where('content_id', $content->id)->first();

        $this->assertSame('Cambiada', $seo->description);
        $this->assertSame('palabra, clave', $seo->keywords);
    }

    #[Test]
    public function saves_the_social_media_fields(): void
    {
        $content = $this->makeContent();

        $seo = $this->service->upsert($content, [
            'og_title' => 'Título para compartir',
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index, follow',
        ]);

        $this->assertSame('Título para compartir', $seo->og_title);
        $this->assertSame('summary_large_image', $seo->twitter_card);
        $this->assertSame('index, follow', $seo->robots);
    }
}
