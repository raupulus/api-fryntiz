<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentPageFormatEnum;
use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentPage;
use App\Models\File;
use App\Models\User;
use App\Services\Content\ContentFileService;
use App\Services\Content\ContentPageFormatService;
use App\Services\Http\PublicUrlFetcher;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Intervention\Image\Laravel\Facades\Image;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * Lo que el editor de un contenido necesita del servidor (F4 del plan de
 * contenidos del 2026-09-24): subir ficheros, descargar una imagen pegada por
 * URL y leer los metadatos de un enlace.
 *
 * Los ficheros de prueba de `tests/Fixtures/uploads` son reales (generados con
 * `magick` y `exiftool`): una foto girada con GPS y modelo del móvil, la misma
 * en HEIC, un PDF con autor, un HTML y un SVG con código.
 */
class EditorJsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private const FIXTURES = __DIR__.'/../../Fixtures/uploads';

    /**
     * Las claves que devolvía la subida de `main`, y que ya guardan los
     * bloques de las páginas publicadas.
     */
    private const MAIN_KEYS = [
        'url', 'path', 'url_thumbnail', 'path-thumbnail', 'url_large', 'path-large', 'content_id',
        'content_file_id', 'file_id', 'module', 'title', 'alt', 'name', 'size', 'extension', 'mime', 'file_type_image',
    ];

    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailablePageRawSeeder)->run();

        $this->useTemporaryStorage();
    }

    /**
     * Un Editor con un contenido suyo.
     */
    private function asAuthor(): User
    {
        $user = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $this->content = Content::factory()->create(['author_id' => $user->id]);
        $this->actingAs($user);

        return $user;
    }

    private function fixture(string $name, ?string $mime = null): UploadedFile
    {
        $copy = sys_get_temp_dir().'/'.uniqid('editor-', true).'-'.$name;
        copy(self::FIXTURES.'/'.$name, $copy);

        return new UploadedFile($copy, $name, $mime ?? (string) mime_content_type($copy), null, true);
    }

    private function upload(UploadedFile $file): TestResponse
    {
        return $this->postJson(route('admin.contents.editor.files.store', $this->content), ['file' => $file]);
    }

    /**
     * El dominio de prueba no existe: la resolución de nombres se sustituye por
     * una IP pública.
     */
    private function withFakeDns(): void
    {
        $this->app->instance(PublicUrlFetcher::class, new class extends PublicUrlFetcher
        {
            protected function resolve(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }

    // ── Subida ───────────────────────────────────────────────────────────────

    #[Test]
    public function a_phone_photo_is_stored_as_webp_rotated_and_without_its_metadata(): void
    {
        $this->asAuthor();

        $response = $this->upload($this->fixture('foto-gps.jpg'))->assertOk()->assertJsonPath('success', 1);

        // Exactamente las claves de `main`.
        $this->assertEqualsCanonicalizing(self::MAIN_KEYS, array_keys($response->json('file')));

        $file = File::query()->findOrFail($response->json('file.file_id'));
        $bytes = (string) file_get_contents($file->storagePathFile);

        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertStringEndsWith('.webp', $file->name);
        $this->assertStringStartsWith('RIFF', $bytes);

        // Sin GPS ni modelo del móvil.
        $this->assertStringNotContainsString('iPhone', $bytes);
        $this->assertStringNotContainsString('Apple', $bytes);
        $this->assertStringNotContainsString('GPS', $bytes);

        // Girada: era 640×480 con la orientación «90° a la derecha», y la
        // marca roja de la esquina superior izquierda queda arriba a la derecha.
        $this->assertSame([480, 640], [(int) $file->width, (int) $file->height]);
        $this->assertStringStartsWith('d', Image::decodePath($file->storagePathFile)->colorAt(470, 100)->toHex());

        // Copias pequeñas y vínculo con el contenido.
        $this->assertNotEmpty($file->thumbnails()->pluck('key')->all());
        $this->assertNotNull(ContentFile::query()->where('content_id', $this->content->id)->where('file_id', $file->id)->first());
        $this->assertSame($this->content->id, $response->json('file.content_id'));
        $this->assertStringNotContainsString('base64', (string) $response->json('file.url'));
    }

    #[Test]
    public function a_heic_photo_becomes_webp(): void
    {
        if (! File::canReadWithImagick('image/heic')) {
            $this->markTestSkipped('Este entorno no tiene Imagick con HEIC (php-imagick y libheif).');
        }

        $this->asAuthor();

        $response = $this->upload($this->fixture('foto.heic', 'image/heic'))->assertOk()->assertJsonPath('success', 1);

        $file = File::query()->findOrFail($response->json('file.file_id'));
        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertGreaterThan(0, (int) $file->width);
    }

    #[Test]
    public function without_heic_support_the_message_says_so(): void
    {
        $this->asAuthor();

        $this->app->bind(ContentFileService::class, fn ($app) => new class($app->make(PublicUrlFetcher::class)) extends ContentFileService
        {
            protected function canRead(string $mime): bool
            {
                return false;
            }
        });

        $this->upload($this->fixture('foto.heic', 'image/heic'))
            ->assertStatus(422)
            ->assertExactJson(['success' => 0, 'message' => 'Este servidor no puede leer fotos HEIC: conviértela a JPG.']);
    }

    #[Test]
    public function a_pdf_is_stored_untouched_with_its_metadata(): void
    {
        $this->asAuthor();

        $response = $this->upload($this->fixture('doc.pdf'))->assertOk();
        $file = File::query()->findOrFail($response->json('file.file_id'));

        $this->assertSame(sha1_file(self::FIXTURES.'/doc.pdf'), sha1_file($file->storagePathFile));
        $this->assertSame($file->url, $response->json('file.url'));
    }

    #[Test]
    public function only_images_and_pdfs_are_shown_in_the_browser_and_the_rest_is_downloaded(): void
    {
        $this->asAuthor();

        $served = function (string $name): TestResponse {
            $file = File::query()->findOrFail($this->upload($this->fixture($name))->assertOk()->json('file.file_id'));

            return $this->get(route('file.get', ['module' => $file->module, 'id' => $file->id]))->assertOk();
        };

        foreach (['pagina.html', 'dibujo.svg'] as $dangerous) {
            $this->assertStringStartsWith('attachment', (string) $served($dangerous)->headers->get('Content-Disposition'), $dangerous);
        }

        foreach (['foto-gps.jpg', 'doc.pdf'] as $safe) {
            $response = $served($safe);
            $this->assertStringStartsNotWith('attachment', (string) $response->headers->get('Content-Disposition'), $safe);
            $this->assertStringContainsString('max-age=300', (string) $response->headers->get('Cache-Control'));
            $this->assertNotNull($response->headers->get('Last-Modified'));
        }
    }

    #[Test]
    public function an_image_over_20_mb_is_refused_with_its_size(): void
    {
        $this->asAuthor();

        $this->upload(UploadedFile::fake()->create('enorme.jpg', 21 * 1024, 'image/jpeg'))
            ->assertStatus(422)
            ->assertJsonPath('success', 0)
            ->assertJsonPath('message', 'La imagen pesa 21 MB y el máximo para imágenes es 20 MB.');
    }

    #[Test]
    public function another_file_over_50_mb_is_refused_with_its_size(): void
    {
        $this->asAuthor();

        $this->upload(UploadedFile::fake()->create('enorme.zip', 51 * 1024, 'application/zip'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'El fichero pesa 51 MB y el máximo es 50 MB.');
    }

    #[Test]
    public function without_a_file_it_responds_with_a_validation_error(): void
    {
        $this->asAuthor();

        $this->postJson(route('admin.contents.editor.files.store', $this->content), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ── Permisos y ritmo ─────────────────────────────────────────────────────

    #[Test]
    public function uploading_requires_being_authenticated(): void
    {
        $this->content = Content::factory()->create();

        $this->post(route('admin.contents.editor.files.store', $this->content), ['file' => UploadedFile::fake()->image('foto.jpg')])
            ->assertRedirect();
    }

    #[Test]
    public function an_editor_without_access_to_the_content_gets_a_403(): void
    {
        $this->asAuthor();
        $foreign = Content::factory()->create();

        $this->postJson(route('admin.contents.editor.files.store', $foreign), ['file' => UploadedFile::fake()->image('foto.jpg')])
            ->assertForbidden();
    }

    #[Test]
    public function a_regular_user_or_a_deactivated_account_cannot_upload(): void
    {
        $this->content = Content::factory()->create();

        foreach ([['role_id' => UserRoleEnum::User->value, 'is_active' => true], ['role_id' => UserRoleEnum::Admin->value, 'is_active' => false]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));

            $this->postJson(route('admin.contents.editor.files.store', $this->content), ['file' => UploadedFile::fake()->image('foto.jpg')])
                ->assertForbidden();
        }
    }

    #[Test]
    public function the_31st_request_in_a_minute_is_a_429(): void
    {
        $this->asAuthor();
        Http::fake();

        for ($i = 1; $i <= 30; $i++) {
            $this->getJson(route('admin.contents.editor.url-metadata', ['content' => $this->content, 'url' => 'http://127.0.0.1/']))->assertOk();
        }

        $this->getJson(route('admin.contents.editor.url-metadata', ['content' => $this->content, 'url' => 'http://127.0.0.1/']))
            ->assertStatus(429);
    }

    // ── Direcciones: metadatos e imágenes por URL ────────────────────────────

    /**
     * Lo que no puede pasar: que el editor sirva para leer la red interna.
     * `169.254.169.254` es el servicio de metadatos de media nube.
     *
     * @return array<string, array{string}>
     */
    public static function forbiddenUrls(): array
    {
        return [
            'bucle' => ['http://127.0.0.1:9200/'],
            'localhost' => ['http://localhost/admin'],
            'red privada' => ['http://192.168.1.1/'],
            'metadatos de nube' => ['http://169.254.169.254/latest/meta-data/'],
            'fichero local' => ['file:///etc/passwd'],
            'otro esquema' => ['gopher://ejemplo.test/'],
            'sin esquema' => ['ejemplo.test'],
            'vacía' => [''],
        ];
    }

    #[Test]
    #[DataProvider('forbiddenUrls')]
    public function metadata_does_not_reach_out_to_the_internal_network(string $url): void
    {
        $this->asAuthor();
        Http::fake();

        $this->getJson(route('admin.contents.editor.url-metadata', ['content' => $this->content, 'url' => $url]))
            ->assertOk()
            ->assertJsonPath('success', 0)
            ->assertJsonPath('meta', []);

        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('forbiddenUrls')]
    public function an_image_by_url_does_not_reach_out_to_the_internal_network(string $url): void
    {
        $this->asAuthor();
        Http::fake();

        $this->postJson(route('admin.contents.editor.files.by-url', $this->content), ['url' => $url])
            ->assertStatus(422)
            ->assertJsonPath('success', 0);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_reads_the_title_and_description_of_a_page(): void
    {
        $this->asAuthor();
        $this->withFakeDns();

        Http::fake(['*' => Http::response(
            '<html><head><title>Una página</title><meta name="description" content="Lo que cuenta">'
            .'<meta property="og:image" content="https://ejemplo.test/foto.jpg"></head></html>',
        )]);

        $this->getJson(route('admin.contents.editor.url-metadata', ['content' => $this->content, 'url' => 'https://ejemplo.test/pagina']))
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonPath('meta.title', 'Una página')
            ->assertJsonPath('meta.description', 'Lo que cuenta')
            ->assertJsonPath('meta.image.url', 'https://ejemplo.test/foto.jpg');
    }

    #[Test]
    public function a_failing_page_does_not_blow_up(): void
    {
        $this->asAuthor();
        $this->withFakeDns();
        Http::fake(['*' => Http::response('', 500)]);

        $this->getJson(route('admin.contents.editor.url-metadata', ['content' => $this->content, 'url' => 'https://ejemplo.test/']))
            ->assertOk()
            ->assertJsonPath('success', 0);
    }

    #[Test]
    public function an_image_pasted_by_url_is_downloaded_and_stored_like_an_upload(): void
    {
        $this->asAuthor();
        $this->withFakeDns();
        Http::fake(['*' => Http::response((string) file_get_contents(self::FIXTURES.'/foto-gps.jpg'), 200, ['Content-Type' => 'image/jpeg'])]);

        $response = $this->postJson(route('admin.contents.editor.files.by-url', $this->content), ['url' => 'https://ejemplo.test/fotos/playa.jpg'])
            ->assertOk()
            ->assertJsonPath('success', 1);

        $file = File::query()->findOrFail($response->json('file.file_id'));
        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertSame('playa.jpg', $file->original_name);
        $this->assertStringNotContainsString('iPhone', (string) file_get_contents($file->storagePathFile));
    }

    #[Test]
    public function something_that_is_not_an_image_by_url_is_refused(): void
    {
        $this->asAuthor();
        $this->withFakeDns();
        Http::fake(['*' => Http::response('<html>no</html>', 200, ['Content-Type' => 'text/html'])]);

        $this->postJson(route('admin.contents.editor.files.by-url', $this->content), ['url' => 'https://ejemplo.test/'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Esa dirección no es una imagen.');
    }

    // ── De punta a punta ─────────────────────────────────────────────────────

    #[Test]
    public function a_page_with_an_attachment_uploaded_by_the_new_route_is_saved(): void
    {
        $this->asAuthor();
        $file = $this->upload($this->fixture('doc.pdf'))->assertOk()->json('file');

        $page = ContentPage::create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        $json = (string) json_encode(['blocks' => [['id' => 'a1', 'type' => 'attaches', 'data' => ['file' => $file, 'title' => 'Informe']]]]);

        app(ContentPageFormatService::class)->save($page, ContentPageFormatEnum::EditorJs, $json);

        $this->assertStringContainsString('Informe', (string) $page->refresh()->content);
        $this->assertStringContainsString((string) $file['url'], (string) $page->content);
    }

    #[Test]
    public function the_caption_becomes_the_alt_text_only_when_it_changes(): void
    {
        $this->asAuthor();
        $data = $this->upload($this->fixture('foto-gps.jpg'))->assertOk()->json('file');
        $service = app(ContentPageFormatService::class);
        $page = ContentPage::create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        $image = fn (string $caption): string => (string) json_encode(['blocks' => [['id' => 'i1', 'type' => 'image', 'data' => ['file' => $data, 'caption' => $caption]]]]);

        $service->save($page, ContentPageFormatEnum::EditorJs, $image('Atardecer en <b>Chipiona</b>'));
        $file = File::query()->findOrFail($data['file_id']);
        $this->assertSame('Atardecer en Chipiona', $file->alt);
        $this->assertSame('Atardecer en Chipiona', $file->title);

        // Un `alt` escrito a mano se respeta mientras nadie cambie el pie.
        $file->update(['alt' => 'Escrito a mano']);
        $service->save($page->refresh(), ContentPageFormatEnum::EditorJs, $image('Atardecer en <b>Chipiona</b>'));
        $this->assertSame('Escrito a mano', $file->refresh()->alt);

        $service->save($page->refresh(), ContentPageFormatEnum::EditorJs, $image('Otro pie'));
        $this->assertSame('Otro pie', $file->refresh()->alt);
    }

    #[Test]
    public function a_file_of_another_content_does_not_get_its_alt_changed(): void
    {
        $this->asAuthor();
        $data = $this->upload($this->fixture('foto-gps.jpg'))->assertOk()->json('file');
        $original = File::query()->findOrFail($data['file_id'])->alt;

        // Otra página, de otro contenido, que apunta al mismo `file_id`.
        $other = ContentPage::create(['content_id' => Content::factory()->create()->id, 'title' => 'Otra', 'slug' => 'otra', 'order' => 1]);
        app(ContentPageFormatService::class)->save($other, ContentPageFormatEnum::EditorJs, (string) json_encode(['blocks' => [
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => $data, 'caption' => 'Pie ajeno']],
        ]]));

        $this->assertSame($original, File::query()->findOrFail($data['file_id'])->alt);
    }
}
