<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\ContentPageFormatEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentTaxonomies;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentRelations;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\ContributorsRelationManager;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\GalleriesRelationManager;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\RelatedRelationManager;
use App\Models\Content\Content;
use App\Models\GalleryImage;
use App\Services\Content\ContentFileUsageService;
use App\Services\Content\ContentPageFormatService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\BuildsApiContents;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * Huella, 304, caché del servidor y visitas de la API de contenidos (P6/F4 y
 * H1 de la auditoría de contenidos; F9 del plan del 2026-09-24).
 */
class ContentApiCacheTest extends ApiTestCase
{
    use BuildsApiContents;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    /**
     * @var array<string, mixed>
     */
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareApiContents();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        // Como en producción: sin el bloque `debug`.
        config(['app.debug' => false]);

        $this->fixture = $this->richContent();
        // La hora forma parte de la clave de la caché: quieta, para que sólo
        // cambie por lo que se edita.
        $this->travelTo(now()->startOfHour()->addMinutes(10));
    }

    private function content(): Content
    {
        return $this->fixture['content'];
    }

    private function detail(array $headers = []): TestResponse
    {
        return $this->withHeaders($headers)->getJson($this->contentUrl($this->content(), '?include=all'));
    }

    private function views(): int
    {
        return (int) DB::table('content_daily_views')->where('content_id', $this->content()->id)->sum('views');
    }

    // ── Huella y 304 ────────────────────────────────────────────────────────

    #[Test]
    public function every_public_route_carries_its_fingerprint_and_a_public_cache_of_a_minute(): void
    {
        $slug = $this->platform->slug;
        $urls = [
            $this->contentUrl($this->content()),
            $this->contentUrl($this->content(), '/pages'),
            $this->contentUrl($this->content(), '/related'),
            "/api/v2/platforms/{$slug}",
            "/api/v2/platforms/{$slug}/contents",
            "/api/v2/platforms/{$slug}/contents/highlights",
            "/api/v2/platforms/{$slug}/tags",
            "/api/v2/platforms/{$slug}/categories",
        ];

        foreach ($urls as $url) {
            $response = $this->getJson($url)->assertOk();

            $this->assertSame('"'.hash('xxh128', (string) $response->getContent()).'"', $response->headers->get('ETag'), $url);
            $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'), $url);
            $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'), $url);
        }
    }

    #[Test]
    public function the_same_fingerprint_answers_304_without_body_and_after_an_edit_a_full_200(): void
    {
        $etag = (string) $this->detail()->assertOk()->headers->get('ETag');

        $notModified = $this->detail(['If-None-Match' => $etag]);
        $notModified->assertStatus(304);
        $this->assertSame('', $notModified->getContent());

        $this->content()->update(['title' => 'Título cambiado']);

        $changed = $this->detail(['If-None-Match' => $etag])->assertOk();
        $this->assertNotSame($etag, $changed->headers->get('ETag'));
        $changed->assertJsonPath('data.title', 'Título cambiado');

        // Sin la cabecera, siempre la respuesta completa.
        $this->detail()->assertOk()->assertJsonPath('data.title', 'Título cambiado');
    }

    #[Test]
    public function a_cached_detail_costs_a_single_query(): void
    {
        $this->detail()->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->detail()->assertOk();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        // La del contenido; la visita se suma después de responder.
        $queries = array_values(array_filter($queries, fn (string $sql): bool => ! str_contains($sql, 'content_daily_views')));
        $this->assertCount(1, $queries, implode("\n", $queries));
    }

    // ── Visitas (H1) ────────────────────────────────────────────────────────

    #[Test]
    public function every_detail_request_counts_a_visit_even_from_the_cache_or_with_304_and_pages_do_not(): void
    {
        $etag = (string) $this->detail()->assertOk()->headers->get('ETag');
        $this->assertSame(1, $this->views(), 'Detalle: +1.');

        $this->getJson($this->contentUrl($this->content(), '/pages'))->assertOk();
        $this->getJson($this->contentUrl($this->content(), '/pages/2'))->assertOk();
        $this->getJson($this->contentUrl($this->content(), '/seo'))->assertOk();
        $this->assertSame(1, $this->views(), 'Páginas y partes: +0.');

        $this->detail()->assertOk();
        $this->assertSame(2, $this->views(), 'Detalle desde la caché: +1.');

        $this->detail(['If-None-Match' => $etag])->assertStatus(304);
        $this->assertSame(3, $this->views(), 'Detalle con 304: +1.');
    }

    #[Test]
    public function a_visit_is_counted_without_a_queue_worker(): void
    {
        config(['queue.default' => 'database']);

        $this->detail()->assertOk();

        $this->assertSame(1, $this->views());
        $this->assertSame(0, DB::table('jobs')->count(), 'No se queda esperando en la cola.');
    }

    // ── Matriz de caducidad ─────────────────────────────────────────────────

    /**
     * Cada cosa que se edita en el panel y sale en el detalle.
     *
     * @return array<string, array{string}>
     */
    public static function edits(): array
    {
        $cases = [
            'datos del contenido', 'visibilidad', 'portada', 'texto de una página', 'página nueva', 'página borrada',
            'orden de las páginas (panel)', 'SEO', 'metadatos', 'categorías (panel)', 'etiquetas (panel)',
            'tecnologías (panel)', 'colaborador añadido (panel)', 'colaborador quitado (panel)',
            'relacionado vinculado (panel)', 'relacionado desvinculado (panel)', 'relacionado con otro título',
            'galería vinculada (panel)', 'galería desvinculada (panel)', 'galería renombrada', 'foto añadida',
            'foto quitada', 'textos de un fichero', 'fichero que deja de usarse', 'plataforma',
            'categoría renombrada', 'etiqueta renombrada', 'tecnología renombrada', 'nombre de un colaborador',
        ];

        return array_combine($cases, array_map(fn (string $case): array => [$case], $cases));
    }

    #[Test]
    #[DataProvider('edits')]
    public function every_edit_changes_the_fingerprint_of_the_detail(string $edit): void
    {
        $before = $this->detail()->assertOk();

        $this->actingAs($this->admin);
        $check = $this->edit($edit);

        $after = $this->detail()->assertOk();

        $this->assertNotSame($before->headers->get('ETag'), $after->headers->get('ETag'), "«{$edit}» no cambia la huella: la API seguiría sirviendo lo de antes.");
        $check($after->json('data'));
    }

    /**
     * Hace el cambio y devuelve la comprobación de que el detalle lo enseña.
     */
    private function edit(string $edit): callable
    {
        $f = $this->fixture;
        $content = $this->content();

        switch ($edit) {
            case 'datos del contenido':
                $content->update(['excerpt' => 'Extracto nuevo']);

                return fn (array $data) => $this->assertSame('Extracto nuevo', $data['excerpt']);

            case 'visibilidad':
                $content->update(['is_visible_on_home' => ! $content->is_visible_on_home]);

                return fn (array $data) => $this->assertSame((bool) $content->is_visible_on_home, $data['visibility']['home']);

            case 'portada':
                $content->update(['image_id' => null]);

                return fn (array $data) => $this->assertNull($data['image']);

            case 'texto de una página':
                app(ContentPageFormatService::class)->save($f['pages'][0], ContentPageFormatEnum::Html, '<p>Texto cambiado</p>');

                return fn (array $data) => $this->assertStringContainsString('Texto cambiado', $data['first_page']['body']);

            case 'página nueva':
                $this->addPage($content, 4, 'Conclusiones', '<p>Fin</p>');

                return fn (array $data) => $this->assertCount(4, $data['pages']);

            case 'página borrada':
                $f['pages'][1]->safeDelete();

                return fn (array $data) => $this->assertSame(['Introducción', 'Resultados'], array_column($data['pages'], 'title'));

            case 'orden de las páginas (panel)':
                Livewire::test(ManageContentPages::class, ['record' => $content->getRouteKey(), 'page' => $f['pages'][0]->id])
                    ->call('reorderPages', [$f['pages'][2]->id, $f['pages'][0]->id, $f['pages'][1]->id]);

                return fn (array $data) => $this->assertSame(['Resultados', 'Introducción', 'Montaje'], array_column($data['pages'], 'title'));

            case 'SEO':
                $f['seo']->update(['description' => 'Descripción nueva']);

                return fn (array $data) => $this->assertSame('Descripción nueva', $data['seo']['description']);

            case 'metadatos':
                $f['metadata']->update(['github' => 'https://github.com/raupulus/otra']);

                return fn (array $data) => $this->assertSame('https://github.com/raupulus/otra', $data['metadata']['github']);

            case 'categorías (panel)':
                $this->taxonomies(['categories' => [$f['weather']->id], 'subcategories' => []]);

                return fn (array $data) => $this->assertSame([], $data['taxonomies']['subcategories']);

            case 'etiquetas (panel)':
                $this->taxonomies(['tags' => []]);

                return fn (array $data) => $this->assertSame([], $data['taxonomies']['tags']);

            case 'tecnologías (panel)':
                $laravel = $this->technology('Laravel');
                $this->taxonomies(['technologies' => [$f['php']->id, $laravel->id]]);

                return fn (array $data) => $this->assertSame(['Laravel', 'PHP'], array_column($data['technologies'], 'name'));

            case 'colaborador añadido (panel)':
                $newcomer = $this->editor('Recién llegada');
                $this->relations(ContributorsRelationManager::class)
                    ->callAction(TestAction::make('addContributor')->table(), ['user_id' => $newcomer->id]);

                return fn (array $data) => $this->assertContains('Recién llegada', array_column($data['contributors'], 'name'));

            case 'colaborador quitado (panel)':
                $this->relations(ContributorsRelationManager::class)
                    ->callAction(TestAction::make('removeContributor')->table($f['contributor']));

                return fn (array $data) => $this->assertSame([], $data['contributors']);

            case 'relacionado vinculado (panel)':
                $other = $this->published(['title' => 'Vinculado ahora', 'published_at' => now()->subYears(2)]);
                $this->relations(RelatedRelationManager::class)
                    ->callAction(TestAction::make('attach')->table(), ['recordId' => $other->id]);

                return fn (array $data) => $this->assertSame(['Otro proyecto', 'Vinculado ahora'], array_slice(array_column($data['related'], 'title'), 0, 2));

            case 'relacionado desvinculado (panel)':
                $this->relations(RelatedRelationManager::class)
                    ->callAction(TestAction::make('detach')->table($f['related']));

                // Es de otro tipo: no vuelve a entrar al completar.
                return fn (array $data) => $this->assertNotContains('Otro proyecto', array_column($data['related'], 'title'));

            case 'relacionado con otro título':
                $f['related']->update(['title' => 'Título del relacionado']);

                return fn (array $data) => $this->assertSame('Título del relacionado', $data['related'][0]['title']);

            case 'galería vinculada (panel)':
                $gallery = $this->gallery('Otra galería', 1);
                $this->relations(GalleriesRelationManager::class)
                    ->callAction(TestAction::make('attach')->table(), ['recordId' => $gallery->id]);

                return fn (array $data) => $this->assertContains('Otra galería', array_column($data['galleries'], 'name'));

            case 'galería desvinculada (panel)':
                $this->relations(GalleriesRelationManager::class)
                    ->callAction(TestAction::make('detach')->table($f['gallery']));

                return fn (array $data) => $this->assertSame([], $data['galleries']);

            case 'galería renombrada':
                $f['gallery']->update(['name' => 'Nombre nuevo']);

                return fn (array $data) => $this->assertSame('Nombre nuevo', $data['galleries'][0]['name']);

            case 'foto añadida':
                GalleryImage::query()->create(['gallery_id' => $f['gallery']->id, 'image_id' => $this->photo('tercera.jpg')->id, 'order' => 3, 'caption' => 'Tercera']);

                return fn (array $data) => $this->assertCount(3, $data['galleries'][0]['images']);

            case 'foto quitada':
                GalleryImage::query()->where('gallery_id', $f['gallery']->id)->firstOrFail()->delete();

                return fn (array $data) => $this->assertCount(1, $data['galleries'][0]['images']);

            case 'textos de un fichero':
                $f['file']->update(['alt' => 'Texto alternativo nuevo']);

                return fn (array $data) => $this->assertSame('Texto alternativo nuevo', $data['files'][0]['alt']);

            case 'fichero que deja de usarse':
                // La portada se quita sin eventos: sólo avisa el repaso de
                // ficheros en uso, como tras guardar una página.
                DB::table('contents')->where('id', $content->id)->update(['image_id' => null]);
                app(ContentFileUsageService::class)->refresh($content->id);

                return fn (array $data) => $this->assertSame([], $data['files']);

            case 'plataforma':
                $this->platform->update(['title' => 'Web renombrada']);

                return fn (array $data) => $this->assertSame('Web renombrada', $data['platform']['title']);

            case 'categoría renombrada':
                $f['weather']->update(['name' => 'Tiempo']);

                return fn (array $data) => $this->assertSame('Tiempo', $data['taxonomies']['categories'][0]['name']);

            case 'etiqueta renombrada':
                $f['raspberry']->update(['name' => 'Raspberry Pi']);

                return fn (array $data) => $this->assertSame('Raspberry Pi', $data['taxonomies']['tags'][0]['name']);

            case 'tecnología renombrada':
                $f['php']->update(['name' => 'PHP 8.5']);

                return fn (array $data) => $this->assertSame('PHP 8.5', $data['technologies'][0]['name']);

            case 'nombre de un colaborador':
                $f['contributor']->update(['name' => 'Otro nombre']);

                return fn (array $data) => $this->assertStringContainsString('Otro nombre', $data['contributors'][0]['name']);
        }

        $this->fail("Cambio desconocido: {$edit}");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function taxonomies(array $data): void
    {
        Livewire::test(EditContentTaxonomies::class, ['record' => $this->content()->getRouteKey()])
            ->fillForm($data)
            ->call('save')
            ->assertHasNoFormErrors();
    }

    private function relations(string $manager): Testable
    {
        return Livewire::test($manager, ['ownerRecord' => $this->content(), 'pageClass' => ManageContentRelations::class]);
    }
}
