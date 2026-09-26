<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageVersion;
use App\Models\File;
use App\Models\FileThumbnail;
use App\Models\User;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * Ficheros de contenido que ya no usa nada (C2 de la auditoría de contenidos;
 * F6 del plan del 2026-09-24), con ficheros de verdad en el disco.
 */
class ContentFileUsageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private ContentPageFormatService $pages;

    private User $user;

    private Content $content;

    private ContentPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        $this->useTemporaryStorage();

        $this->pages = app(ContentPageFormatService::class);
        $this->user = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->actingAs($this->user);
        $this->content = Content::factory()->create(['author_id' => $this->user->id]);
        $this->page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
    }

    /**
     * Sube una imagen al contenido, como el editor.
     */
    private function upload(): File
    {
        $payload = app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('foto.jpg', 800, 600));

        return File::query()->findOrFail($payload['file_id']);
    }

    /**
     * @param  list<File>  $files
     */
    private function editorJs(string $text, array $files = []): string
    {
        $blocks = [['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]]];

        foreach ($files as $i => $file) {
            $blocks[] = ['id' => "i{$i}", 'type' => 'image', 'data' => ['file' => ['url' => $file->url, 'file_id' => $file->id], 'caption' => '']];
        }

        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => $blocks]);
    }

    private function save(string $content, Format $format = Format::EditorJs): void
    {
        $this->pages->savePage($this->page, [], $format, $content, author: $this->user);
        $this->page->refresh();
    }

    private function unusedSince(File $file): ?Carbon
    {
        return ContentFile::query()->where('file_id', $file->id)->first()?->unused_since;
    }

    #[Test]
    public function a_file_that_only_remains_in_a_version_is_still_used(): void
    {
        $photo = $this->upload();
        $this->save($this->editorJs('Con foto', [$photo]));

        $this->save($this->editorJs('Sin foto'));

        $this->assertNull($this->unusedSince($photo), 'Se puede volver a esa versión: la foto se usa.');
    }

    #[Test]
    public function a_file_gone_from_everything_is_marked_and_comes_back_when_used_again(): void
    {
        $photo = $this->upload();
        $this->save($this->editorJs('Con foto', [$photo]));
        $this->save($this->editorJs('Sin foto'));

        // Se va la versión que la nombraba (a los 30 días, la tarea diaria).
        Carbon::setTestNow(now()->addDays(31));
        $this->artisan('content:prune-drafts-and-versions')->assertSuccessful();
        $this->assertSame(0, ContentPageVersion::query()->count());
        $this->assertNotNull($this->unusedSince($photo));

        // Vuelve a la página: se desmarca al guardar.
        $this->save($this->editorJs('Otra vez con foto', [$photo]));
        $this->assertNull($this->unusedSince($photo));
    }

    #[Test]
    public function an_upload_never_used_is_marked_at_the_next_save(): void
    {
        $photo = $this->upload();
        $this->assertNull($this->unusedSince($photo));

        $this->save($this->editorJs('Texto sin la foto'));

        $this->assertNotNull($this->unusedSince($photo));
    }

    #[Test]
    public function a_draft_or_a_cover_keeps_a_file_in_use(): void
    {
        $inDraft = $this->upload();
        $cover = $this->upload();

        // El borrador de otra persona (el de quien guarda se borra al guardar).
        $other = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        app(ContentPageDraftService::class)->save($other, $this->content, $this->page, Format::EditorJs, $this->editorJs('Borrador', [$inDraft]));
        $this->page->update(['image_id' => $cover->id]);
        $this->save($this->editorJs('Guardado sin fotos'));

        $this->assertNull($this->unusedSince($inDraft));
        $this->assertNull($this->unusedSince($cover));
    }

    #[Test]
    public function a_file_linked_by_its_url_in_markdown_or_by_a_thumbnail_is_used(): void
    {
        $byUrl = $this->upload();
        $byThumbnail = $this->upload();
        $thumbnail = FileThumbnail::query()->where('file_id', $byThumbnail->id)->firstOrFail();

        $this->save(sprintf(
            "![Uno](%s)\n\n![Dos](%s)\n",
            route('file.get', ['module' => 'content', 'id' => $byUrl->id, 'slug' => $byUrl->name]),
            route('file.thumbnail.get', ['module' => 'content', 'id' => $thumbnail->id, 'slug' => $thumbnail->name]),
        ), Format::Markdown);

        $this->assertNull($this->unusedSince($byUrl));
        $this->assertNull($this->unusedSince($byThumbnail));
    }

    #[Test]
    public function the_purge_waits_thirty_days_and_then_deletes_the_file_its_thumbnails_and_its_row(): void
    {
        $photo = $this->upload();
        $path = $photo->storagePathFile;
        $thumbnails = FileThumbnail::query()->where('file_id', $photo->id)->get();
        $this->assertFileExists($path);
        $this->assertNotEmpty($thumbnails);

        $this->save($this->editorJs('Sin la foto'));

        Carbon::setTestNow(now()->addDays(29));
        $this->artisan('content:purge-unused-files')->assertSuccessful();
        $this->assertFileExists($path);
        $this->assertNotNull(File::query()->find($photo->id));

        Carbon::setTestNow(now()->addDays(2));
        $this->artisan('content:purge-unused-files')->expectsOutputToContain('Ficheros borrados: 1')->assertSuccessful();

        $this->assertFileDoesNotExist($path);
        $this->assertNull(File::query()->find($photo->id));
        $this->assertSame(0, FileThumbnail::query()->where('file_id', $photo->id)->count());
        $this->assertSame(0, ContentFile::withTrashed()->where('file_id', $photo->id)->count());

        foreach ($thumbnails as $thumbnail) {
            $this->assertFileDoesNotExist($thumbnail->storagePathFile);
        }
    }

    #[Test]
    public function the_purge_keeps_a_file_that_something_else_uses(): void
    {
        $photo = $this->upload();
        $this->save($this->editorJs('Sin la foto'));

        // Otro contenido la ha cogido de portada.
        Content::factory()->create(['author_id' => $this->user->id, 'image_id' => $photo->id]);

        Carbon::setTestNow(now()->addDays(31));
        $this->artisan('content:purge-unused-files')->expectsOutputToContain('Conservados porque algo los usa: 1')->assertSuccessful();

        $this->assertFileExists($photo->storagePathFile);
        $this->assertNull($this->unusedSince($photo));
    }

    #[Test]
    public function the_trash_keeps_the_files_and_deleting_for_good_marks_them(): void
    {
        $photo = $this->upload();
        $this->save($this->editorJs('Con foto', [$photo]));
        $second = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Otra', 'slug' => 'otra', 'order' => 2]);

        // A la papelera: se puede restaurar, no se marca nada ni se borra nada.
        $this->page->safeDelete();
        $this->pages->save($second, Format::EditorJs, $this->editorJs('Otra página'));
        $this->assertNull($this->unusedSince($photo));
        $this->assertFileExists($photo->storagePathFile);

        // Eliminada para siempre: sí.
        $this->page->forceDelete();
        $this->assertNotNull($this->unusedSince($photo));
    }

    #[Test]
    public function deleting_a_content_for_good_marks_its_files_and_keeps_their_rows(): void
    {
        $photo = $this->upload();
        $this->save($this->editorJs('Con foto', [$photo]));

        $this->content->delete();
        $this->assertNull($this->unusedSince($photo), 'En la papelera no se marca.');
        $this->assertFileExists($photo->storagePathFile);

        $this->content->forceDelete();

        $row = ContentFile::query()->where('file_id', $photo->id)->first();
        $this->assertNotNull($row?->unused_since);
        $this->assertNull($row->content_id);

        Carbon::setTestNow(now()->addDays(31));
        $this->artisan('content:purge-unused-files')->assertSuccessful();
        $this->assertFileDoesNotExist($photo->storagePathFile);
    }
}
