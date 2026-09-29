<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\User;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentImageService;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * Servidor de la pestaña de imágenes de una página (H2 de la auditoría de
 * contenidos del 2026-09-24): recortar o sustituir sin tocar los bloques,
 * editar textos sin tocar el fichero, y saber dónde más se usa cada imagen.
 */
class ContentImageServiceTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private Content $content;

    private ContentImageService $images;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailablePageRawSeeder)->run();
        $this->useTemporaryStorage();

        $user = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->actingAs($user);
        $this->content = Content::factory()->create(['author_id' => $user->id]);
        $this->images = app(ContentImageService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadPhoto(int $width = 2000, int $height = 1500): array
    {
        return app(ContentFileService::class)->store($this->content, UploadedFile::fake()->image('foto.jpg', $width, $height));
    }

    private function pageWith(array $data, int $order = 1): ContentPage
    {
        $page = ContentPage::create(['content_id' => $this->content->id, 'title' => "Página {$order}", 'slug' => "pagina-{$order}", 'order' => $order]);
        app(ContentPageFormatService::class)->save($page, ContentPageFormatEnum::EditorJs, (string) json_encode(['blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Texto']],
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => $data, 'caption' => 'Foto']],
        ]]));

        return $page->refresh();
    }

    /**
     * @return array<string, string>
     */
    private function thumbnailHashes(File $file): array
    {
        return $file->thumbnails()->get()->mapWithKeys(fn ($t): array => [$t->id => (string) sha1_file((string) $t->storagePathFile)])->all();
    }

    #[Test]
    public function cropping_keeps_the_file_and_its_copies_and_changes_the_pixels(): void
    {
        $data = $this->uploadPhoto();
        $file = File::query()->findOrFail($data['file_id']);
        $originalHash = sha1_file($file->storagePathFile);
        $thumbnails = $this->thumbnailHashes($file);

        $this->images->crop($file, 100, 100, 1500, 900);

        $file->refresh();
        $this->assertSame($data['file_id'], $file->id);
        $this->assertSame([1500, 900], [(int) $file->width, (int) $file->height]);
        $this->assertNotSame($originalHash, sha1_file($file->storagePathFile));

        // Las mismas copias (mismos ids, así que las mismas URLs de los
        // bloques), con los píxeles nuevos.
        $after = $this->thumbnailHashes($file);
        $this->assertSame(array_keys($thumbnails), array_keys($after));
        foreach ($thumbnails as $id => $hash) {
            $this->assertNotSame($hash, $after[$id], "La copia {$id} no ha cambiado.");
        }

        // El bloque no se ha tocado y sigue sirviéndose.
        $this->get((string) $data['url'])->assertOk();
    }

    #[Test]
    public function a_crop_outside_the_image_is_refused(): void
    {
        $file = File::query()->findOrFail($this->uploadPhoto(800, 600)['file_id']);

        $this->expectException(\InvalidArgumentException::class);

        $this->images->crop($file, 500, 0, 400, 100);
    }

    #[Test]
    public function replacing_keeps_the_file_id_and_copies(): void
    {
        $data = $this->uploadPhoto();
        $file = File::query()->findOrFail($data['file_id']);
        $thumbnailIds = $file->thumbnails()->pluck('id')->sort()->values()->all();

        $this->images->replace($file, UploadedFile::fake()->image('otra.png', 1600, 1600));

        $file->refresh();
        $this->assertSame([1600, 1600], [(int) $file->width, (int) $file->height]);
        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertSame($thumbnailIds, $file->thumbnails()->pluck('id')->sort()->values()->all());
    }

    #[Test]
    public function editing_the_texts_does_not_touch_a_byte_of_the_file(): void
    {
        $file = File::query()->findOrFail($this->uploadPhoto()['file_id']);
        $hash = sha1_file($file->storagePathFile);
        $thumbnails = $this->thumbnailHashes($file);

        $this->images->updateTexts($file, 'Nuevo <b>título</b>', 'Texto alternativo');

        $file->refresh();
        $this->assertSame('Nuevo título', $file->title);
        $this->assertSame('Texto alternativo', $file->alt);
        $this->assertSame($hash, sha1_file($file->storagePathFile));
        $this->assertSame($thumbnails, $this->thumbnailHashes($file));
    }

    #[Test]
    public function it_lists_the_images_of_a_page_and_where_else_each_one_is_used(): void
    {
        $data = $this->uploadPhoto();
        $page = $this->pageWith($data, 1);
        $this->pageWith($data, 2);

        $cover = File::query()->findOrFail($this->uploadPhoto(900, 900)['file_id']);
        $page->update(['image_id' => $cover->id]);
        $this->content->update(['image_id' => $data['file_id']]);

        $gallery = Gallery::query()->create(['name' => 'Galería de prueba', 'slug' => 'galeria-de-prueba']);
        GalleryImage::query()->create(['gallery_id' => $gallery->id, 'image_id' => $data['file_id'], 'order' => 1]);

        $this->assertSame([$data['file_id'], $cover->id], $this->images->pageImages($page->refresh())->pluck('id')->all());

        $usages = collect($this->images->usages(File::query()->findOrFail($data['file_id'])));
        $this->assertSame(2, $usages->where('type', 'page')->count());
        $this->assertSame(1, $usages->where('type', 'content-cover')->count());
        $this->assertSame(1, $usages->where('type', 'gallery')->count());
        $this->assertSame(['page-cover'], collect($this->images->usages($cover))->pluck('type')->all());
    }

    #[Test]
    public function an_image_is_found_by_its_url_in_a_markdown_page_and_in_where_else_it_is_used(): void
    {
        $data = $this->uploadPhoto(800, 600);
        $file = File::query()->findOrFail($data['file_id']);
        $thumbnail = $file->thumbnails()->firstOrFail();
        $formats = app(ContentPageFormatService::class);

        // Escritas a mano: sin `file_id`, sólo la URL del fichero o de una
        // de sus miniaturas.
        $byUrl = ContentPage::create(['content_id' => $this->content->id, 'title' => 'En Markdown', 'slug' => 'en-markdown', 'order' => 1]);
        $formats->save($byUrl, ContentPageFormatEnum::Markdown, "Texto\n\n![Foto]({$data['url']})");
        $byThumbnail = ContentPage::create(['content_id' => $this->content->id, 'title' => 'Con miniatura', 'slug' => 'con-miniatura', 'order' => 2]);
        $formats->save($byThumbnail, ContentPageFormatEnum::Markdown, "Texto\n\n![Foto]({$thumbnail->url})");

        $this->assertSame([$file->id], $this->images->pageImages($byUrl->refresh())->pluck('id')->all());
        $this->assertSame([$file->id], $this->images->pageImages($byThumbnail->refresh())->pluck('id')->all());

        $pages = collect($this->images->usages($file))->where('type', 'page')->pluck('id')->sort()->values()->all();
        $this->assertSame([$byUrl->id, $byThumbnail->id], $pages);
    }

    #[Test]
    public function a_file_of_another_content_in_a_block_is_not_an_image_of_the_page(): void
    {
        // Desde «Imágenes» se recorta y se sustituye: un id en el JSON no
        // puede servir para tocar un fichero ajeno.
        $foreign = app(ContentFileService::class)->store(Content::factory()->create(), UploadedFile::fake()->image('ajena.jpg', 400, 300));
        $own = $this->uploadPhoto(800, 600);

        $page = $this->pageWith($foreign);
        $this->assertSame([], $this->images->pageImages($page)->pluck('id')->all());

        $page = $this->pageWith($own, 2);
        $this->assertSame([$own['file_id']], $this->images->pageImages($page)->pluck('id')->all());
    }
}
