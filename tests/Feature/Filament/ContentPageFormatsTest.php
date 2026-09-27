<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\User;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editor de páginas con fuente única: el cambio de formato no guarda nada
 * hasta que se confirma, se puede deshacer y deja copia.
 */
class ContentPageFormatsTest extends TestCase
{
    use RefreshDatabase;

    private Content $content;

    private ContentPageFormatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->actingAs(User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]));

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->content = Content::factory()->create();
        $this->service = app(ContentPageFormatService::class);
    }

    private function editorJs(string $text): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.29.0', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
        ]]);
    }

    /**
     * Como se carga en el formulario: indentado, para leerlo en «JSON en crudo».
     */
    private function pretty(string $json): string
    {
        return (string) json_encode(json_decode($json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function editorJsPage(string $text = 'Texto original'): ContentPage
    {
        $page = ContentPage::create([
            'content_id' => $this->content->id,
            'title' => 'Página',
            'slug' => 'pagina',
            'order' => 1,
        ]);

        $this->service->save($page, Format::EditorJs, $this->editorJs($text));

        return $page->refresh();
    }

    private function manager(ContentPage|string|null $page = null): Testable
    {
        return Livewire::test(ManageContentPages::class, [
            'record' => $this->content->getRouteKey(),
            'page' => $page instanceof ContentPage ? $page->id : $page,
        ]);
    }

    private function convertTo(Format $target): TestAction
    {
        return TestAction::make('convertTo'.Str::studly($target->value))->schemaComponent('format');
    }

    #[Test]
    public function an_existing_page_opens_in_its_own_format(): void
    {
        $page = $this->editorJsPage();

        $this->manager($page)
            ->assertSchemaStateSet([
                'source_format' => 'editorjs',
                'stored_format' => 'editorjs',
                'content_json' => $this->pretty($this->editorJs('Texto original')),
            ]);
    }

    #[Test]
    public function converting_opens_the_new_format_without_saving_anything(): void
    {
        $page = $this->editorJsPage();
        $before = $page->content;

        $this->manager($page)
            ->callAction($this->convertTo(Format::Markdown))
            ->assertHasNoErrors()
            ->assertSchemaStateSet([
                'source_format' => 'markdown',
                'content_markdown' => "Texto original\n",
                'pending_change' => 'convert',
                'original_format' => 'editorjs',
            ]);

        $page->refresh();
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page));
        $this->assertSame($before, $page->content);
    }

    #[Test]
    public function a_format_change_is_not_saved_without_ticking_the_confirmation(): void
    {
        $page = $this->editorJsPage();

        $this->manager($page)
            ->callAction($this->convertTo(Format::Markdown))
            ->call('save')
            ->assertHasErrors(['data.confirm_format_change' => 'accepted']);

        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page->refresh()));
    }

    #[Test]
    public function a_confirmed_format_change_is_saved_and_leaves_a_backup(): void
    {
        $page = $this->editorJsPage();

        $this->manager($page)
            ->callAction($this->convertTo(Format::Markdown))
            ->fillForm(['content_markdown' => "Texto original\n\nY algo más", 'confirm_format_change' => true])
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame(Format::Markdown, $this->service->sourceFormat($page));
        $this->assertStringContainsString('Y algo más', (string) $page->content);
        $this->assertSame($this->editorJs('Texto original'), $this->service->latestBackup($page)?->content);
    }

    #[Test]
    public function undo_brings_back_the_original_format_as_it_was(): void
    {
        $page = $this->editorJsPage();

        $this->manager($page)
            ->callAction($this->convertTo(Format::Html))
            ->callAction(TestAction::make('undoFormatChange')->schemaComponent('format'))
            ->assertSchemaStateSet([
                'source_format' => 'editorjs',
                'content_json' => $this->pretty($this->editorJs('Texto original')),
                'pending_change' => null,
            ]);
    }

    #[Test]
    public function the_previous_version_can_be_brought_back(): void
    {
        $page = $this->editorJsPage();
        $this->service->save($page, Format::Markdown, 'Versión en Markdown');
        $version = $this->service->latestBackup($page->refresh());

        // Desde el historial: se abre sin guardar, como un cambio de formato.
        $this->manager($page)
            ->call('loadVersion', $version?->id)
            ->assertSchemaStateSet([
                'source_format' => 'editorjs',
                'content_json' => $this->pretty($this->editorJs('Texto original')),
                'pending_change' => 'restore',
            ])
            ->fillForm(['confirm_format_change' => true])
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page));
        $this->assertStringContainsString('Texto original', (string) $page->content);
        // Lo que había (el Markdown) pasa a su vez al historial.
        $this->assertSame('Versión en Markdown', $this->service->latestBackup($page)?->content);
    }

    #[Test]
    public function saving_markdown_with_an_h2_warns_but_saves(): void
    {
        $page = $this->editorJsPage();
        $this->service->save($page, Format::Markdown, 'Texto');

        $this->manager($page->refresh())
            ->fillForm(['content_markdown' => "## Sección\n\nTexto"])
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified('La página tiene 1 título h1 o h2');

        $this->assertStringContainsString('<h2>Sección</h2>', (string) $page->refresh()->content);
    }

    #[Test]
    public function saving_markdown_that_starts_at_h3_does_not_warn(): void
    {
        $page = $this->editorJsPage();
        $this->service->save($page, Format::Markdown, 'Texto');

        $this->manager($page->refresh())
            ->fillForm(['content_markdown' => "### Sección\n\nTexto"])
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotNotified('La página tiene 1 título h1 o h2');
    }

    #[Test]
    public function a_new_page_starts_in_editorjs_and_saves_its_html(): void
    {
        $this->manager('new')
            ->assertSchemaStateSet(['source_format' => 'editorjs'])
            ->fillForm(['title' => 'Nueva', 'content_json' => $this->editorJs('Recién escrita')])
            ->call('save')
            ->assertHasNoErrors();

        $page = ContentPage::query()->where('title', 'Nueva')->firstOrFail();
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page));
        $this->assertStringContainsString('Recién escrita', (string) $page->content);
    }
}
