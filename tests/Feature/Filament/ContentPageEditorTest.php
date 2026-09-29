<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\ContentPageVersionReasonEnum as Reason;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageDraft;
use App\Models\Content\ContentPageVersion;
use App\Models\File;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageLockService;
use App\Support\ApiCacheVersion;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * La pantalla de páginas (E1, C6, D1, P3, P4, H2 y G4 de la auditoría de
 * contenidos; F8 del plan del 2026-09-24).
 */
class ContentPageEditorTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private ContentPageFormatService $pages;

    private User $admin;

    private User $editor;

    private Content $content;

    private ContentPage $first;

    private ContentPage $second;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        $this->useTemporaryStorage();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->pages = app(ContentPageFormatService::class);
        $this->admin = User::factory()->create(['name' => 'Admin', 'role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->editor = User::factory()->create(['name' => 'Pepe', 'role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);

        $this->content = Content::factory()->create(['author_id' => $this->admin->id]);
        app(ContentContributorService::class)->add($this->content, $this->editor);

        $this->first = $this->page('Primera', 'primera', 1);
        $this->second = $this->page('Segunda', 'segunda', 2);

        $this->actingAs($this->admin);
    }

    private function page(string $title, string $slug, int $order): ContentPage
    {
        $page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => $title, 'slug' => $slug, 'order' => $order]);
        $this->pages->save($page, Format::EditorJs, $this->editorJs("Texto de {$title}"));

        return $page->refresh();
    }

    /**
     * @param  list<array<string, mixed>>  $extra
     */
    private function editorJs(string $text, array $extra = []): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
            ...$extra,
        ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function open(ContentPage|string|null $page = null): Testable
    {
        return Livewire::test(ManageContentPages::class, [
            'record' => $this->content->getRouteKey(),
            'page' => $page instanceof ContentPage ? $page->id : $page,
        ]);
    }

    // ── Lista de páginas ────────────────────────────────────────────────────

    #[Test]
    public function changing_page_saves_the_current_one_first(): void
    {
        $this->open($this->first)
            ->fillForm(['content_json' => $this->editorJs('Cambiado antes de irme')])
            ->call('saveBeforeLeaving')
            ->assertReturned(true);

        $this->assertStringContainsString('Cambiado antes de irme', (string) $this->first->refresh()->content);
    }

    #[Test]
    public function if_saving_fails_it_does_not_change_page(): void
    {
        $this->open($this->first)
            // Una imagen sin fichero: un bloque sin su dato principal.
            ->fillForm(['content_json' => $this->editorJs('No vale', [['id' => 'i1', 'type' => 'image', 'data' => ['caption' => 'Sin fichero']]])])
            ->call('saveBeforeLeaving')
            ->assertHasErrors(['data.content_json'])
            ->assertReturned(null);

        $this->assertStringContainsString('Texto de Primera', (string) $this->first->refresh()->content);
    }

    #[Test]
    public function without_changes_it_changes_page_without_saving(): void
    {
        $before = $this->first->updated_at;
        Carbon::setTestNow(now()->addMinute());

        $this->open($this->first)->call('saveBeforeLeaving')->assertReturned(true);

        $this->assertEquals($before, $this->first->refresh()->updated_at);
        $this->assertSame(0, ContentPageVersion::query()->count());
    }

    #[Test]
    public function reordering_renumbers_from_one_without_gaps_and_without_touching_the_pages(): void
    {
        $third = $this->page('Tercera', 'tercera', 7);
        $foreign = ContentPage::query()->create(['content_id' => Content::factory()->create()->id, 'title' => 'Ajena', 'slug' => 'ajena', 'order' => 1]);
        $before = $third->updated_at;
        Carbon::setTestNow(now()->addMinute());

        $this->open($this->first)->call('reorderPages', [$third->id, $foreign->id, $this->first->id, $this->second->id]);

        $this->assertSame([1, 2, 3], [$third->refresh()->order, $this->first->refresh()->order, $this->second->refresh()->order]);
        $this->assertSame(1, $foreign->refresh()->order, 'Una página de otro contenido no se toca.');
        $this->assertEquals($before, $third->updated_at, 'Reordenar no es cambiar la página (D4).');
    }

    #[Test]
    public function deleting_a_page_moves_up_the_ones_behind_without_a_conflict_for_whoever_edits_them(): void
    {
        $third = $this->page('Tercera', 'tercera', 3);
        $before = $third->updated_at;

        // Pepe tiene abierta la tercera cuando el admin borra la segunda.
        $this->actingAs($this->editor);
        $pepe = $this->open($third);
        Carbon::setTestNow(now()->addMinute());
        $version = ApiCacheVersion::current();

        $this->second->safeDelete();

        $this->assertSame([1, 2], [$this->first->refresh()->order, $third->refresh()->order]);
        $this->assertEquals($before, $third->updated_at, 'Subir de puesto no es cambiar la página (D4).');
        $this->assertSame($version + 1, ApiCacheVersion::current(), 'La caché de la API se invalida una vez, no una por página.');

        $pepe->fillForm(['content_json' => $this->editorJs('Escrito mientras tanto')])->call('save')->assertHasNoErrors();
        $this->assertStringContainsString('Escrito mientras tanto', (string) $third->refresh()->content);
    }

    #[Test]
    public function the_list_marks_the_pages_with_a_draft_and_the_ones_someone_else_has_open(): void
    {
        $third = $this->page('Tercera', 'tercera', 3);
        $drafts = app(ContentPageDraftService::class);
        $drafts->save($this->admin, $this->content, $this->second, Format::EditorJs, $this->editorJs('Borrador de la segunda'), 'Segunda', 'segunda');
        $drafts->save($this->admin, $this->content, $this->first, Format::EditorJs, $this->editorJs('Borrador de la abierta'), 'Primera', 'primera');
        app(ContentPageLockService::class)->acquire($third, $this->editor, 'pestaña-de-pepe');

        $screen = $this->open($this->first)->assertSee('La está editando Pepe', false);
        $marks = $screen->instance()->getPagesMarks($screen->instance()->getPagesList());

        // La abierta no se marca: su borrador ya lo dice la barra.
        $this->assertSame([$this->second->id => true], $marks['drafts']);
        $this->assertSame([$third->id => 'La está editando Pepe'], $marks['locked']);
    }

    #[Test]
    public function a_new_page_is_created_at_the_end_when_saved(): void
    {
        $this->open('new')
            ->assertSet('pageId', null)
            ->fillForm(['title' => 'Mi página nueva', 'content_json' => $this->editorJs('Recién escrita')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('content-page-created');

        $page = ContentPage::query()->where('title', 'Mi página nueva')->sole();
        $this->assertSame(3, $page->order);
        $this->assertSame('mi-pagina-nueva', $page->slug);
    }

    // ── Slug (C6) ───────────────────────────────────────────────────────────

    #[Test]
    public function an_empty_slug_comes_from_the_title_and_an_occupied_one_is_refused(): void
    {
        $this->open('new')
            ->fillForm(['title' => 'Segunda'])
            ->assertSchemaStateSet(['slug' => 'segunda-2']);

        $this->open($this->first)
            ->fillForm(['slug' => 'segunda'])
            ->assertHasErrors(['data.slug'])
            ->assertSee('Ese slug ya lo usa la página «Segunda»')
            ->call('save')
            ->assertHasErrors(['data.slug']);

        $this->assertSame('primera', $this->first->refresh()->slug);
    }

    // ── Autoguardado y borradores (D1) ─────────────────────────────────────

    #[Test]
    public function autosave_keeps_a_draft_without_duplicating_it_and_renews_the_lock(): void
    {
        $editor = $this->open($this->first);
        $lockedAt = $this->first->refresh()->locked_at;

        Carbon::setTestNow(now()->addSeconds(30));
        $editor->fillForm(['content_json' => $this->editorJs('Sin guardar')])
            ->call('autosave')
            ->assertSet('hasUnsavedChanges', true)
            ->assertNotSet('autosavedAt', null);

        $editor->call('autosave')->call('autosave');

        $this->assertSame(1, ContentPageDraft::query()->count());
        $this->assertStringContainsString('Sin guardar', (string) ContentPageDraft::query()->value('content'));
        $this->assertStringContainsString('Texto de Primera', (string) $this->first->refresh()->content, 'El autoguardado no guarda la página.');
        $this->assertTrue($this->first->locked_at?->gt($lockedAt));
    }

    #[Test]
    public function a_page_with_a_cover_is_not_unsaved_after_an_autosave_without_changes(): void
    {
        // El campo de subida guarda la portada como `[uuid => id]`, y se leía
        // como «sin portada»: toda página con portada salía cambiada.
        $cover = File::addFile(UploadedFile::fake()->image('portada.jpg', 800, 450), 'content-pages', webpOriginal: true);
        $this->first->update(['image_id' => $cover->id]);

        $this->open($this->first->refresh())
            ->call('autosave')
            ->assertSet('hasUnsavedChanges', false)
            ->call('saveBeforeLeaving')
            ->assertReturned(true);

        $this->assertSame(0, ContentPageDraft::query()->count());
        $this->assertSame(0, ContentPageVersion::query()->count());
    }

    #[Test]
    public function a_cover_id_from_the_browser_that_is_not_the_page_s_own_is_ignored(): void
    {
        $foreign = File::addFile(UploadedFile::fake()->image('ajena.jpg', 400, 300), 'cv');

        $this->open($this->first)
            ->set('data.image_id', ['x' => $foreign->id])
            ->call('autosave')
            ->call('save');

        $this->assertNull($this->first->refresh()->image_id);
        $this->assertNull(ContentPageDraft::query()->value('image_id'));
    }

    #[Test]
    public function a_draft_is_offered_on_opening_and_restoring_it_keeps_the_saved_one_in_the_history(): void
    {
        app(ContentPageDraftService::class)->save($this->admin, $this->content, $this->first, Format::EditorJs, $this->editorJs('Del borrador'), 'Primera', 'primera');

        $this->open($this->first)
            ->assertNotSet('draftOffer', null)
            ->assertSee('Tienes un borrador de')
            ->callAction('restoreDraft');

        $this->assertStringContainsString('Del borrador', (string) $this->first->refresh()->content);
        $this->assertSame(Reason::DraftRestore, ContentPageVersion::query()->latest('id')->first()?->reason);
    }

    // ── Bloqueo (P4) ────────────────────────────────────────────────────────

    #[Test]
    public function a_page_locked_by_someone_else_is_read_only(): void
    {
        app(ContentPageLockService::class)->acquire($this->first, $this->editor, 'pestaña-de-pepe');

        $this->open($this->first)
            ->assertSet('readOnly', true)
            ->assertSee('Pepe la está editando')
            ->assertFormFieldDisabled('title')
            ->call('save');

        $this->assertStringContainsString('Texto de Primera', (string) $this->first->refresh()->content);
    }

    #[Test]
    public function an_administrator_can_force_the_unlock_and_the_holder_loses_it(): void
    {
        $locks = app(ContentPageLockService::class);
        $locks->acquire($this->first, $this->editor, 'pestaña-de-pepe');

        $this->open($this->first)
            ->assertActionVisible('forceUnlock')
            ->callAction('forceUnlock')
            ->assertSet('readOnly', false);

        $this->assertFalse($locks->renew($this->first, $this->editor, 'pestaña-de-pepe'));
    }

    #[Test]
    public function whoever_loses_the_lock_goes_read_only_at_the_next_autosave_keeping_the_draft(): void
    {
        $this->actingAs($this->editor);
        $editor = $this->open($this->first)->assertSet('readOnly', false);

        app(ContentPageLockService::class)->forceUnlock($this->first, $this->admin);

        $editor->fillForm(['content_json' => $this->editorJs('Lo de la editora')])
            ->call('autosave')
            ->assertSet('readOnly', true)
            ->assertSee('Has perdido el bloqueo')
            ->assertDispatched('content-page-read-only', readOnly: true);

        $this->assertStringContainsString('Lo de la editora', (string) ContentPageDraft::query()->where('user_id', $this->editor->id)->value('content'));
    }

    #[Test]
    public function a_read_only_screen_starts_editing_when_the_lock_is_released(): void
    {
        // Así queda una recarga: el bloqueo de la carga anterior aún no se ha
        // soltado cuando se abre esta.
        $locks = app(ContentPageLockService::class);
        $locks->acquire($this->first, $this->admin, 'carga-anterior');
        $screen = $this->open($this->first)->assertSet('readOnly', true);

        $locks->release($this->first, $this->admin, 'carga-anterior');

        $screen->call('autosave')
            ->assertSet('readOnly', false)
            ->assertDispatched('content-page-read-only', readOnly: false);
    }

    #[Test]
    public function an_editor_cannot_force_the_unlock(): void
    {
        app(ContentPageLockService::class)->acquire($this->first, $this->admin, 'pestaña-del-admin');
        $this->actingAs($this->editor);

        $this->open($this->first)
            ->assertSet('readOnly', true)
            ->assertActionHidden('forceUnlock');
    }

    #[Test]
    public function after_a_crash_the_same_user_edits_here_again(): void
    {
        // La pestaña de antes murió sin soltar el bloqueo.
        app(ContentPageLockService::class)->acquire($this->first, $this->admin, 'pestaña-muerta');

        $this->open($this->first)
            ->assertSet('readOnly', true)
            ->assertSee('Ya la tienes abierta en otra pestaña')
            ->assertActionHidden('forceUnlock')
            ->assertActionHidden('deletePage')
            ->assertDontSee('Eliminar página')
            ->callAction('takeOver')
            ->assertSet('readOnly', false);
    }

    #[Test]
    public function closing_the_tab_releases_the_lock(): void
    {
        $locks = app(ContentPageLockService::class);
        $locks->acquire($this->first, $this->admin, 'mi-pestaña');

        $this->post(route('admin.contents.pages.lock.release', [$this->content, $this->first]), ['token' => 'mi-pestaña'])->assertNoContent();

        $this->assertSame('free', $locks->state($this->first, $this->editor)->status);
    }

    // ── Historial (G5) ──────────────────────────────────────────────────────

    #[Test]
    public function an_editor_cannot_restore_a_version_in_html_and_an_administrator_can(): void
    {
        $this->pages->savePage($this->first, [], Format::Html, '<p>En HTML</p>', null, $this->admin);
        Carbon::setTestNow(now()->addMinute());
        $this->pages->savePage($this->first->refresh(), [], Format::Markdown, 'En **Markdown**', Reason::FormatChange, $this->admin);
        $html = ContentPageVersion::query()->get()->firstOrFail(fn (ContentPageVersion $version): bool => $version->format === Format::Html);

        $this->actingAs($this->editor);
        $this->open($this->first->refresh())
            ->mountAction('history')
            ->assertMountedActionModalSee('Sólo un administrador')
            ->call('loadVersion', $html->id)
            ->assertNotified('No puedes recuperar esta versión')
            ->assertSet('data.source_format', Format::Markdown->value)
            ->assertSet('hasUnsavedChanges', false);

        $this->actingAs($this->admin);
        app(ContentPageLockService::class)->forceUnlock($this->first, $this->admin);
        $this->open($this->first)
            ->call('loadVersion', $html->id)
            ->assertSet('data.source_format', Format::Html->value);
    }

    // ── Imágenes (H2) ───────────────────────────────────────────────────────

    #[Test]
    public function the_images_tab_edits_the_texts_of_the_page_images_only(): void
    {
        $upload = app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('foto.jpg', 800, 600));
        $photo = File::query()->findOrFail($upload['file_id']);
        $other = File::query()->findOrFail(app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('otra.jpg', 400, 300))['file_id']);
        $this->pages->save($this->first, Format::EditorJs, $this->editorJs('Con foto', [
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => $photo->url, 'file_id' => $photo->id], 'caption' => '']],
        ]));

        $editor = $this->open($this->first->refresh());
        $this->assertSame([[2]], array_map(fn (array $card): array => $card['blocks'], $editor->instance()->imageCards()));

        $editor->call('saveImageTexts', $photo->id, 'Mi foto', 'Una foto de prueba')
            ->call('saveImageTexts', $other->id, 'No', 'No');

        $this->assertSame('Una foto de prueba', $photo->refresh()->alt);
        $this->assertNotSame('No', $other->refresh()->alt, 'Un fichero que no es de la página no se toca.');
    }

    #[Test]
    public function the_images_tab_crops_keeping_the_file_and_its_urls(): void
    {
        $photo = File::query()->findOrFail(app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('foto.jpg', 800, 600))['file_id']);
        $this->pages->save($this->first, Format::EditorJs, $this->editorJs('Con foto', [
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => $photo->url, 'file_id' => $photo->id], 'caption' => '']],
        ]));
        $url = $photo->url;

        $this->open($this->first->refresh())->call('cropImage', $photo->id, 100, 50, 400, 300);

        $photo->refresh();
        $this->assertSame([400, 300], [(int) $photo->width, (int) $photo->height]);
        $this->assertSame($url, $photo->url);
    }

    #[Test]
    public function the_images_tab_replaces_keeping_the_file_and_its_urls(): void
    {
        $photo = File::query()->findOrFail(app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('foto.jpg', 800, 600))['file_id']);
        $this->pages->save($this->first, Format::EditorJs, $this->editorJs('Con foto', [
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => $photo->url, 'file_id' => $photo->id], 'caption' => '']],
        ]));
        $url = $photo->url;

        $this->open($this->first->refresh())
            ->set('replacement', UploadedFile::fake()->image('nueva.jpg', 500, 400))
            ->call('replaceImage', $photo->id)
            ->assertNotified('Imagen sustituida');

        $photo->refresh();
        $this->assertSame([500, 400], [(int) $photo->width, (int) $photo->height]);
        $this->assertSame($url, $photo->url);
    }

    #[Test]
    public function uploads_ask_for_the_current_csrf_token(): void
    {
        // Con un Admin, no un SuperAdmin: éste se salta las políticas y no
        // habría visto que la ruta no convertía `{content}` en el modelo.
        $this->getJson(route('admin.contents.editor.csrf-token', $this->content))
            ->assertOk()
            ->assertJson(['token' => csrf_token()]);

        $outsider = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $this->actingAs($outsider)->getJson(route('admin.contents.editor.csrf-token', $this->content))->assertForbidden();
    }

    // ── Límite de Livewire (G4) ─────────────────────────────────────────────

    #[Test]
    public function livewire_accepts_8_mb_and_a_300_kb_page_is_saved(): void
    {
        $this->assertSame(8 * 1024 * 1024, config('livewire.payload.max_size'));

        $paragraphs = array_map(fn (int $i): array => ['id' => "p{$i}", 'type' => 'paragraph', 'data' => ['text' => str_repeat("Párrafo {$i} ", 30)]], range(1, 900));
        $json = (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => $paragraphs], JSON_UNESCAPED_UNICODE);
        $this->assertGreaterThan(300 * 1024, strlen($json));

        $this->open($this->first)->fillForm(['content_json' => $json])->call('save')->assertHasNoErrors();

        $this->assertStringContainsString('Párrafo 900', (string) $this->first->refresh()->content);
    }

    #[Test]
    public function the_section_opens_the_first_page_and_an_unknown_page_is_not_found(): void
    {
        $this->open()->assertSet('pageId', $this->first->id);

        $this->get(ContentResource::getUrl('pages', ['record' => $this->content, 'page' => 999999]))->assertNotFound();
    }
}
