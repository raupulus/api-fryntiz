<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Enums\UserRoleEnum;
use App\Models\File;
use App\Models\FileThumbnail;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function storage_path;
use function unlink;

/**
 * Servir un fichero que no está en el disco no revienta.
 *
 * La fila y el fichero se separan con facilidad: alguien borra a mano en
 * `storage/`, una restauración de base de datos trae filas cuyos ficheros no
 * viajaron, o `storage_path` queda a null y el accessor devuelve **cadena
 * vacía**.
 *
 * `download()` lo comprobaba; `get()` y `resizeAndGet()` no, así que iban
 * directas a `response()->file()`, que lanza `FileNotFoundException` → 500 en
 * una ruta pública que además sirve las imágenes de las webs (AR-E04).
 */
class FileServingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function ghostFile(array $extra = []): File
    {
        $file = new File;

        $file->forceFill(array_merge([
            'module' => 'content',
            'path' => 'content',
            'storage_path' => 'public/content',
            'name' => 'este-fichero-no-existe.jpg',
            'original_name' => 'foto.jpg',
            'size' => 1024,
            'alt' => '',
            'title' => '',
            'is_private' => false,
        ], $extra))->save();

        return $file;
    }

    #[Test]
    public function get_on_a_file_missing_from_disk_returns_404_and_not_500(): void
    {
        $file = $this->ghostFile();

        $this->get("/file/get/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function download_on_a_file_missing_from_disk_returns_404(): void
    {
        $file = $this->ghostFile();

        $this->get("/file/download/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function get_with_an_empty_storage_path_returns_404_and_not_500(): void
    {
        // `getStoragePathFileAttribute()` devuelve '' cuando no hay
        // `storage_path`, y `response()->file('')` es un 500.
        $file = $this->ghostFile(['storage_path' => null]);

        $this->get("/file/get/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function an_unknown_id_returns_the_generic_image_with_404(): void
    {
        // Se sigue devolviendo la IMAGEN para no romper la maqueta de la web,
        // pero con un 404: antes salía con un 200 sobre algo que no existe, y
        // eso se lo cree una caché.
        $response = $this->get('/file/get/content/999999');

        $response->assertStatus(404);
        $this->assertStringContainsString('image', (string) $response->headers->get('Content-Type'));
    }

    #[Test]
    public function the_module_in_the_url_has_to_match(): void
    {
        // `{module}` viajaba en la ruta y no se usaba para nada: el fichero se
        // resolvía sólo por su id, así que /file/get/hardware/123 servía tan
        // ricamente un fichero de `content` (AR-A03).
        $file = $this->ghostFile(['module' => 'content']);

        $this->get("/file/get/hardware/{$file->id}")->assertStatus(404);
    }

    // ───────────────────────── Miniaturas (AR-ERR-01) ────────────────────────

    /**
     * `FileThumbnailController::get()` tenía exactamente el mismo agujero que
     * `FileController` había cerrado ya: resolvía la fila y llamaba a
     * `response()->file()` sin comprobar el disco. Una miniatura registrada cuyo
     * fichero no esté en el servidor —storage sin migrar, sincronización a
     * medias— devolvía un 500 público.
     */
    private function ghostThumbnail(File $file, array $extra = []): FileThumbnail
    {
        $thumbnail = new FileThumbnail;

        $thumbnail->forceFill(array_merge([
            'file_id' => $file->id,
            'module' => $file->module,
            'path' => 'content/medium',
            'storage_path' => 'public/content/medium',
            'name' => 'esta-miniatura-no-existe.jpg',
            'key' => 'medium',
            'width' => 640,
            'height' => 480,
            'size' => 1024,
        ], $extra))->save();

        return $thumbnail;
    }

    #[Test]
    public function a_thumbnail_missing_from_disk_returns_404_and_not_500(): void
    {
        $thumbnail = $this->ghostThumbnail($this->ghostFile());

        $this->get("/file/thumbnail/get/content/{$thumbnail->id}")->assertStatus(404);
    }

    #[Test]
    public function a_thumbnail_with_an_empty_storage_path_returns_404_and_not_500(): void
    {
        // El accessor devuelve '' cuando no hay `storage_path`, y
        // `response()->file('')` también es un 500.
        $thumbnail = $this->ghostThumbnail($this->ghostFile(), ['storage_path' => null]);

        $this->get("/file/thumbnail/get/content/{$thumbnail->id}")->assertStatus(404);
    }

    #[Test]
    public function a_nonexistent_thumbnail_returns_404(): void
    {
        $this->get('/file/thumbnail/get/content/999999')->assertStatus(404);
    }

    #[Test]
    public function an_orphaned_thumbnail_returns_404(): void
    {
        // Sin fichero padre no se sabe de quién es, así que no se sirve: la
        // privacidad vive en el padre (N175).
        $file = $this->ghostFile();
        $thumbnail = $this->ghostThumbnail($file);
        $file->forceDelete();

        $this->get("/file/thumbnail/get/content/{$thumbnail->id}")->assertStatus(404);
    }

    // ──────────────── Privacidad de la miniatura: bypass de admin ────────────

    /**
     * Rutas de disco que crean estos tests, para borrarlas al terminar.
     *
     * @var list<string>
     */
    private array $createdPhysicalPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPhysicalPaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    /**
     * Igual que {@see ghostThumbnail}, pero con un fichero real en disco:
     * estos tests comprueban qué se sirve, no sólo que no reviente.
     */
    private function realThumbnail(File $file): FileThumbnail
    {
        $directory = storage_path('app/public/content/medium');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = 'real-thumbnail-'.$file->id.'.jpg';
        $path = $directory.'/'.$name;
        file_put_contents($path, 'real thumbnail content');
        $this->createdPhysicalPaths[] = $path;

        return $this->ghostThumbnail($file, ['name' => $name]);
    }

    /**
     * `BinaryFileResponse` sirve el fichero en streaming: su `getContent()`
     * no trae los bytes, así que `assertSee` no vale aquí. Se comprueba la
     * ruta física que de verdad se sirvió.
     */
    private function pathServedFor(string $thumbnailId): string
    {
        return $this->get("/file/thumbnail/get/content/{$thumbnailId}")
            ->baseResponse
            ->getFile()
            ->getPathname();
    }

    #[Test]
    public function a_private_thumbnail_is_visible_to_its_owner(): void
    {
        $owner = User::factory()->create(['role_id' => UserRoleEnum::User->value]);
        $file = $this->ghostFile(['is_private' => true, 'user_id' => $owner->id]);
        $thumbnail = $this->realThumbnail($file);

        $path = $this->actingAs($owner)->pathServedFor((string) $thumbnail->id);

        $this->assertSame($thumbnail->storage_path_file, $path);
    }

    #[Test]
    public function a_private_thumbnail_is_visible_to_an_administrator_even_if_not_the_owner(): void
    {
        // Regresión: `FileThumbnailController::get()` sólo comparaba
        // `user_id`, sin el bypass de administrador que sí tiene
        // `FileController::canAccess()`. Un administrador que no fuera el
        // dueño literal del fichero veía el marcador de «no encontrado» en
        // vez de la miniatura real — justo lo que sirve `CurrentImage` en el
        // panel.
        $owner = User::factory()->create(['role_id' => UserRoleEnum::User->value]);
        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value]);
        $file = $this->ghostFile(['is_private' => true, 'user_id' => $owner->id]);
        $thumbnail = $this->realThumbnail($file);

        $path = $this->actingAs($admin)->pathServedFor((string) $thumbnail->id);

        $this->assertSame($thumbnail->storage_path_file, $path);
    }

    #[Test]
    public function a_private_thumbnail_is_not_visible_to_another_unprivileged_user(): void
    {
        $owner = User::factory()->create(['role_id' => UserRoleEnum::User->value]);
        $other = User::factory()->create(['role_id' => UserRoleEnum::User->value]);
        $file = $this->ghostFile(['is_private' => true, 'user_id' => $owner->id]);
        $thumbnail = $this->realThumbnail($file);

        $path = $this->actingAs($other)->pathServedFor((string) $thumbnail->id);

        $this->assertNotSame($thumbnail->storage_path_file, $path);
    }
}
