<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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

    /**
     * @param  array<string, mixed>  $extra
     */
    private function ficheroFantasma(array $extra = []): File
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
    public function get_de_un_fichero_que_no_esta_en_disco_responde_404_y_no_500(): void
    {
        $file = $this->ficheroFantasma();

        $this->get("/file/get/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function download_de_un_fichero_que_no_esta_en_disco_responde_404(): void
    {
        $file = $this->ficheroFantasma();

        $this->get("/file/download/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function get_con_storage_path_vacio_responde_404_y_no_500(): void
    {
        // `getStoragePathFileAttribute()` devuelve '' cuando no hay
        // `storage_path`, y `response()->file('')` es un 500.
        $file = $this->ficheroFantasma(['storage_path' => null]);

        $this->get("/file/get/content/{$file->id}")->assertStatus(404);
    }

    #[Test]
    public function un_id_que_no_existe_sigue_devolviendo_la_imagen_generica(): void
    {
        // Comportamiento de siempre: la ruta sirve imágenes de páginas web, así
        // que un id perdido devuelve el marcador en vez de romper la maqueta.
        $this->get('/file/get/content/999999')->assertOk();
    }
}
