<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Filament\Components\EditorJsField;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\User;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageHistoryService;
use App\Services\Content\ContentPageLockService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Guardar una página desde el panel (F3 del plan de contenidos del
 * 2026-09-24): nunca revienta a medias, nunca deja bloques rotos sin avisar,
 * nunca guarda código escondido, y el HTML libre es sólo de administradores.
 */
class ContentPageSavingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private Content $content;

    private ContentPageFormatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->service = app(ContentPageFormatService::class);
    }

    private function actingAsRole(UserRoleEnum $role): User
    {
        $user = User::factory()->create(['role_id' => $role->value, 'is_active' => true]);
        $this->actingAs($user);

        // El Editor edita sus propios contenidos.
        $this->content = Content::factory()->create(['author_id' => $user->id]);

        return $user;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function editorJs(array $blocks): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function page(string $json, string $title = 'Página'): ContentPage
    {
        $page = ContentPage::create(['content_id' => $this->content->id, 'title' => $title, 'slug' => Str::slug($title), 'order' => 1]);
        $this->service->save($page, Format::EditorJs, $json);

        return $page->refresh();
    }

    private function edit(ContentPage $page): Testable
    {
        return Livewire::test(ManageContentPages::class, ['record' => $this->content->getRouteKey(), 'page' => $page->id]);
    }

    // ── Los tres casos reales de A2 ─────────────────────────────────────────

    #[Test]
    public function a_page_with_an_attachment_uploaded_from_the_editor_is_saved(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]));

        // La forma exacta que devuelve hoy `EditorJsController::upload()`.
        $json = $this->editorJs([['id' => 'a1', 'type' => 'attaches', 'data' => [
            'file' => ['url' => 'https://api.test/file/get/9/informe.pdf', 'name' => 'informe.pdf', 'size' => 2048, 'extension' => 'pdf', 'file_id' => 9],
            'title' => '',
        ]]]);

        $this->edit($page)->fillForm(['content_json' => $json])->call('save')->assertHasNoErrors();

        $this->assertStringContainsString('informe.pdf', (string) $page->refresh()->content);
    }

    #[Test]
    public function a_page_with_a_link_card_without_metadata_is_saved(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]));

        $json = $this->editorJs([['id' => 'l1', 'type' => 'linkTool', 'data' => ['link' => 'https://ejemplo.test/bloqueada', 'meta' => []]]]);

        $this->edit($page)->fillForm(['content_json' => $json])->call('save')->assertHasNoErrors();

        $this->assertStringContainsString('href="https://ejemplo.test/bloqueada"', (string) $page->refresh()->content);
    }

    #[Test]
    public function incomplete_json_pasted_by_hand_is_saved(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]));

        $json = $this->editorJs([
            ['id' => 'q1', 'type' => 'quote', 'data' => ['text' => 'Cita sin autor']],
            ['id' => 'a1', 'type' => 'alert', 'data' => ['message' => 'Alerta sin tipo']],
            ['id' => 'e1', 'type' => 'embed', 'data' => ['embed' => 'https://www.youtube.com/embed/x']],
            ['id' => 'w1', 'type' => 'warning', 'data' => ['message' => 'Aviso sin título']],
            ['id' => 't1', 'type' => 'table', 'data' => ['content' => [['A', 'B']]]],
            ['id' => 'h1', 'type' => 'header', 'data' => ['text' => 'Título sin nivel']],
        ]);

        $this->edit($page)->fillForm(['content_json' => $json])->call('save')->assertHasNoErrors();

        $served = (string) $page->refresh()->content;
        foreach (['Cita sin autor', 'Alerta sin tipo', 'youtube.com/embed/x', 'Aviso sin título', '<h3', 'Título sin nivel'] as $expected) {
            $this->assertStringContainsString($expected, $served);
        }
    }

    // ── Lo que no se puede guardar ──────────────────────────────────────────

    #[Test]
    public function a_block_without_its_main_data_is_a_form_error_and_nothing_is_saved(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]), 'Título viejo');

        $component = $this->edit($page)
            ->fillForm([
                'title' => 'Título nuevo',
                'content_json' => $this->editorJs([
                    ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']],
                    ['id' => 'i1', 'type' => 'image', 'data' => ['caption' => 'Sin fichero']],
                ]),
            ])
            ->call('save')
            ->assertHasErrors(['data.content_json']);

        $this->assertStringContainsString(
            'Bloque 2 (imagen): no tiene fichero. Quítalo o vuelve a subir la imagen.',
            implode(' ', $component->errors()->get('data.content_json')),
        );

        $page->refresh();
        $this->assertSame('Título viejo', $page->title);
        $this->assertStringContainsString('Antes', (string) $page->content);
    }

    #[Test]
    public function if_the_content_fails_the_title_is_not_saved_either(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]), 'Título viejo');

        // Un fallo al guardar el contenido, después de haber escrito el título
        // (pasar lo anterior al historial va justo antes de escribir la fuente).
        $history = Mockery::mock(ContentPageHistoryService::class)->makePartial();
        $history->shouldReceive('record')->andThrow(new RuntimeException('Fallo forzado al guardar el contenido.'));
        $this->app->instance(ContentPageHistoryService::class, $history);

        $this->edit($page)
            ->fillForm(['title' => 'Título nuevo', 'content_json' => $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Después']]])])
            ->call('save')
            ->assertNotified('No se ha guardado la página');

        $page->refresh();
        $this->assertSame('Título viejo', $page->title);
        $this->assertStringContainsString('Antes', (string) $page->content);
    }

    #[Test]
    public function if_the_page_is_saved_elsewhere_meanwhile_it_is_not_overwritten_and_the_work_goes_to_the_draft(): void
    {
        $user = $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]));

        $modal = $this->edit($page);

        // Con el bloqueo, sólo pasa tras un desbloqueo forzado: un
        // administrador lo fuerza y guarda mientras la pantalla sigue abierta.
        Carbon::setTestNow(now()->addMinute());
        app(ContentPageLockService::class)->forceUnlock($page, $user);
        $this->service->savePage($page->refresh(), [], Format::EditorJs, $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Otra pestaña']]]), author: $user);

        $modal
            ->fillForm(['content_json' => $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Lo mío']]])])
            ->call('save')
            ->assertNotified('No se ha guardado la página');

        $this->assertStringContainsString('Otra pestaña', (string) $page->refresh()->content);
        $this->assertStringContainsString('Lo mío', (string) app(ContentPageDraftService::class)->find($user, $this->content, $page)?->content);
    }

    // ── Código escondido ────────────────────────────────────────────────────

    #[Test]
    public function hidden_code_is_removed_also_when_an_administrator_saves(): void
    {
        $this->actingAsRole(UserRoleEnum::SuperAdmin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Antes']]]));

        $this->edit($page)
            ->fillForm(['content_json' => $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola <img src=x onerror="alert(1)"> y <b>negrita</b>']]])])
            ->call('save')
            ->assertHasNoErrors();

        $stored = $this->service->sourceContent($page->refresh());
        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringContainsString('<b>negrita</b>', $stored);
        $this->assertStringNotContainsString('onerror', (string) $page->content);
    }

    // ── HTML libre, sólo administradores ────────────────────────────────────

    #[Test]
    public function an_editor_does_not_see_raw_json_nor_the_html_block_nor_the_html_format(): void
    {
        $this->actingAsRole(UserRoleEnum::Editor);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']]]));

        $this->edit($page)
            ->assertSchemaComponentHidden('raw-json')
            ->assertSchemaComponentExists('visual-editor', checkComponentUsing: fn (EditorJsField $field): bool => ! $field->canUseRawHtml())
            ->assertActionHidden(TestAction::make('convertToHtml')->schemaComponent('format'))
            ->assertActionVisible(TestAction::make('convertToMarkdown')->schemaComponent('format'));
    }

    #[Test]
    public function an_administrator_sees_them(): void
    {
        $this->actingAsRole(UserRoleEnum::Admin);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']]]));

        $this->edit($page)
            ->assertSchemaComponentVisible('raw-json')
            ->assertSchemaComponentExists('visual-editor', checkComponentUsing: fn (EditorJsField $field): bool => $field->canUseRawHtml())
            ->assertActionVisible(TestAction::make('convertToHtml')->schemaComponent('format'));
    }

    #[Test]
    public function an_editor_cannot_add_an_html_block_but_keeps_the_ones_already_there(): void
    {
        $this->actingAsRole(UserRoleEnum::Editor);
        $existing = ['id' => 'r1', 'type' => 'raw', 'data' => ['html' => '<section class="x">De un administrador</section>']];
        $page = $this->page($this->editorJs([$existing]));

        // Uno nuevo: no. (La misma pantalla para los dos guardados: una
        // segunda la vería en lectura, porque la primera tiene el bloqueo.)
        $editor = $this->edit($page);
        $editor
            ->fillForm(['content_json' => $this->editorJs([$existing, ['id' => 'r2', 'type' => 'raw', 'data' => ['html' => '<script>x()</script>']]])])
            ->call('save')
            ->assertHasErrors(['data.content_json']);

        $this->assertStringNotContainsString('x()', $this->service->sourceContent($page->refresh()));

        // El que ya estaba, con un párrafo nuevo: sí.
        $editor
            ->fillForm(['content_json' => $this->editorJs([$existing, ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Añadido']]])])
            ->call('save')
            ->assertHasNoErrors();

        $served = (string) $page->refresh()->content;
        $this->assertStringContainsString('De un administrador', $served);
        $this->assertStringContainsString('Añadido', $served);
    }

    #[Test]
    public function the_server_refuses_html_from_an_editor(): void
    {
        $editor = $this->actingAsRole(UserRoleEnum::Editor);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']]]));

        $this->assertNotSame([], $this->service->problems($page, Format::Html, '<p onclick="x()">Hola</p>', $editor));
        $this->assertSame([], $this->service->problems($page, Format::Html, '<p onclick="x()">Hola</p>', User::factory()->create(['role_id' => UserRoleEnum::Admin->value])));
    }

    #[Test]
    public function html_inside_the_markdown_of_an_editor_is_cleaned_and_that_of_an_administrator_is_not(): void
    {
        $editor = $this->actingAsRole(UserRoleEnum::Editor);
        $page = $this->page($this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']]]));
        $markdown = "Texto <img src=x onerror=\"alert(1)\"> con **negrita** y `<code>`\n\n<div onclick=\"x()\">bloque</div>";

        $this->service->save($page, Format::Markdown, $markdown, author: $editor);
        $stored = $this->service->sourceContent($page->refresh());
        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
        $this->assertStringContainsString('**negrita**', $stored);
        $this->assertStringContainsString('`<code>`', $stored);

        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value]);
        $this->service->save($page, Format::Markdown, $markdown, author: $admin);
        $this->assertSame($markdown, $this->service->sourceContent($page->refresh()));
    }
}
