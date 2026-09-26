<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\ContentPageVersionReasonEnum as Reason;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailablePageRaw;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageRaw;
use App\Models\Content\ContentPageVersion;
use App\Services\Content\ContentFormatConverter;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fuente única de una página: lo que se guarda, lo que se regenera y la copia
 * que queda antes de cambiar de formato.
 */
class ContentPageFormatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentPageFormatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->service = app(ContentPageFormatService::class);
    }

    private function page(array $attributes = []): ContentPage
    {
        return ContentPage::create([
            'content_id' => Content::factory()->create()->id,
            'title' => 'Página',
            'slug' => 'pagina',
            'order' => 1,
            'content' => null,
            ...$attributes,
        ]);
    }

    private function typeId(Format $format): int
    {
        return (int) ContentAvailablePageRaw::query()->where('type', $format->rawType())->value('id');
    }

    private function editorJs(string $text): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.29.0', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
        ]]);
    }

    #[Test]
    public function saving_editorjs_regenerates_the_served_html_and_the_markdown(): void
    {
        $page = $this->page();

        $this->assertTrue($this->service->save($page, Format::EditorJs, $this->editorJs('Hola <b>mundo</b>')));

        $page->refresh();
        $this->assertSame($this->typeId(Format::EditorJs), $page->current_page_raw_id);
        $this->assertStringContainsString('Hola <b>mundo</b>', (string) $page->content);
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page));
        $this->assertSame("Hola **mundo**\n", $this->service->contentIn($page, Format::Markdown));
    }

    #[Test]
    public function changing_the_format_keeps_the_previous_source_as_a_backup(): void
    {
        $page = $this->page();
        $original = $this->editorJs('Primera versión');
        $this->service->save($page, Format::EditorJs, $original);

        $this->service->save($page->refresh(), Format::Markdown, "# Nueva\n\nOtra cosa");

        $page->refresh();
        $this->assertSame(Format::Markdown, $this->service->sourceFormat($page));
        $this->assertStringContainsString('<h1>Nueva</h1>', (string) $page->content);

        // La fuente anterior no se pisa: pasa al historial, como cambio de formato.
        $backup = $this->service->latestBackup($page);
        $this->assertNotNull($backup);
        $this->assertSame(Format::EditorJs, $this->service->formatOf($backup));
        $this->assertSame($original, $backup->content);
        $this->assertSame(Reason::FormatChange, $backup->reason);

        // Y ya no como fila borrada de `content_page_raw` (el mecanismo de antes).
        $this->assertSame(0, ContentPageRaw::onlyTrashed()->where('content_page_id', $page->id)->count());

        // Y el Editor.js vivo es el derivado del Markdown nuevo.
        $this->assertStringContainsString('Otra cosa', $this->service->contentIn($page, Format::EditorJs));
    }

    #[Test]
    public function an_empty_editor_does_not_touch_the_page(): void
    {
        $page = $this->page();
        $this->service->save($page, Format::EditorJs, $this->editorJs('Algo'));
        $before = $page->refresh()->content;

        $this->assertFalse($this->service->save($page, Format::Markdown, '   '));

        $page->refresh();
        $this->assertSame($before, $page->content);
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page));
        $this->assertNull($this->service->latestBackup($page));
    }

    #[Test]
    public function emptying_a_page_keeps_a_copy_of_what_it_had(): void
    {
        $page = $this->page();
        $this->service->save($page, Format::EditorJs, $this->editorJs('Algo'));

        $this->service->save($page->refresh(), Format::EditorJs, '{"blocks":[]}');

        $this->assertSame('', (string) $page->refresh()->content);
        $this->assertStringContainsString('Algo', (string) $this->service->latestBackup($page)?->content);
        $this->assertSame(Reason::Emptied, $this->service->latestBackup($page)?->reason);
    }

    #[Test]
    public function saving_the_same_content_again_does_not_add_a_version(): void
    {
        $page = $this->page();
        $this->service->save($page, Format::EditorJs, $this->editorJs('Algo'));

        // El mismo contenido, con otra marca de tiempo del editor e indentado:
        // no ha cambiado nada.
        $same = (string) json_encode(['time' => 999] + json_decode($this->editorJs('Algo'), true), JSON_PRETTY_PRINT);
        $this->service->save($page->refresh(), Format::EditorJs, $same);

        $this->assertSame(0, ContentPageVersion::query()->where('content_page_id', $page->id)->count());

        $this->service->save($page->refresh(), Format::EditorJs, $this->editorJs('Otra cosa'));

        $this->assertSame(1, ContentPageVersion::query()->where('content_page_id', $page->id)->count());
        $this->assertSame(Reason::Save, $this->service->latestBackup($page)?->reason);
    }

    #[Test]
    public function an_unmarked_page_with_editorjs_json_is_editorjs(): void
    {
        // Así están las páginas de la v1 que nunca se guardaron desde la v2.
        $page = $this->page(['content' => '<p>Viejo</p>']);
        ContentPageRaw::create([
            'content_page_id' => $page->id,
            'available_page_raw_id' => $this->typeId(Format::EditorJs),
            'content' => $this->editorJs('Viejo'),
        ]);

        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($page->refresh()));
        $this->assertSame($this->editorJs('Viejo'), $this->service->sourceContent($page));
    }

    #[Test]
    public function an_unmarked_page_with_only_html_is_html_and_its_html_is_backed_up(): void
    {
        $page = $this->page(['content' => '<p>Sólo HTML</p>']);

        $this->assertSame(Format::Html, $this->service->sourceFormat($page));

        $this->service->save($page, Format::Markdown, 'Nuevo');

        $backup = $this->service->latestBackup($page->refresh());
        $this->assertSame(Format::Html, $this->service->formatOf($backup));
        $this->assertSame('<p>Sólo HTML</p>', $backup->content);
    }

    #[Test]
    public function an_empty_new_page_starts_in_editorjs(): void
    {
        $this->assertSame(Format::EditorJs, $this->service->sourceFormat($this->page()));
    }

    #[Test]
    public function a_missing_derived_version_is_converted_on_the_fly(): void
    {
        $page = $this->page(['content' => '<p>Viejo</p>']);
        ContentPageRaw::create([
            'content_page_id' => $page->id,
            'available_page_raw_id' => $this->typeId(Format::EditorJs),
            'content' => $this->editorJs('Viejo'),
        ]);

        $this->assertSame("Viejo\n", $this->service->contentIn($page->refresh(), Format::Markdown));
        $this->assertSame(1, ContentPageRaw::query()->where('content_page_id', $page->id)->count());
    }

    #[Test]
    public function html_as_source_is_served_without_the_block_wrappers(): void
    {
        $page = $this->page();
        $this->service->save($page, Format::EditorJs, $this->editorJs('Hola'));
        $html = app(ContentFormatConverter::class)
            ->convert($this->editorJs('Hola'), Format::EditorJs, Format::Html)->content;

        $this->service->save($page->refresh(), Format::Html, $html);

        $page->refresh();
        $this->assertStringNotContainsString('data-editorjs-block', (string) $page->content);
        $this->assertStringContainsString('data-editorjs-block', $this->service->sourceContent($page));
    }
}
