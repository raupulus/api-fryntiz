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
    public function crea_el_seo_de_un_contenido_que_no_lo_tenia(): void
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
    public function guardar_dos_veces_actualiza_y_no_duplica(): void
    {
        $content = $this->makeContent();

        $primero = $this->service->upsert($content, ['description' => 'Primera']);
        $segundo = $this->service->upsert($content, ['description' => 'Segunda']);

        $this->assertSame($primero->id, $segundo->id);
        $this->assertSame('Segunda', $segundo->fresh()->description);
        $this->assertSame(1, ContentSeo::where('content_id', $content->id)->count());
    }

    #[Test]
    public function cada_contenido_tiene_su_propia_fila_de_seo(): void
    {
        $uno = $this->makeContent();
        $otro = $this->makeContent();

        $this->service->upsert($uno, ['description' => 'La del primero']);
        $this->service->upsert($otro, ['description' => 'La del segundo']);

        $this->assertSame(2, ContentSeo::query()->count());
        $this->assertSame('La del primero', ContentSeo::where('content_id', $uno->id)->first()->description);
    }

    #[Test]
    public function un_upsert_parcial_no_borra_lo_que_no_se_manda(): void
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
    public function guarda_los_campos_de_redes_sociales(): void
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
