<?php

declare(strict_types=1);

namespace Tests\Unit\Files;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

/**
 * Cubre la política de subida de `File::addFile()`: qué se valida, qué no, y
 * qué se hace con los metadatos de las imágenes.
 *
 * Los tres tests de EXIF son la red de seguridad de una decisión que hoy
 * dependería del driver: GD no propaga metadatos al reescribir, así que sin
 * estos tests un cambio de driver o un salto de major de la librería podría
 * reintroducir la geolocalización en las fotos sin que nada avisara.
 */
class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    /** Directorio de trabajo dentro de storage/app, propio de estos tests. */
    private string $directory = 'pruebas-subida';

    protected function setUp(): void
    {
        parent::setUp();

        // No se usa Storage::fake(): el modelo resuelve las rutas con
        // storage_path() —ver File::getStoragePathFileAttribute()—, así que un
        // disco falso dejaría los archivos en un sitio y el modelo los buscaría
        // en otro. Se trabaja sobre el disco real en un directorio propio y se
        // limpia al terminar.
        $this->cleanDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanDirectory();

        parent::tearDown();
    }

    private function cleanDirectory(): void
    {
        foreach (['private', 'public'] as $scope) {
            $path = storage_path('app/'.$scope.'/'.$this->directory);

            if (is_dir($path)) {
                exec('rm -rf '.escapeshellarg($path));
            }
        }
    }

    /** Archivos que hay ahora mismo en el directorio de trabajo. */
    private function filesOnDisk(): array
    {
        $path = storage_path('app/private/'.$this->directory);

        if (! is_dir($path)) {
            return [];
        }

        return array_values(array_diff(scandir($path), ['.', '..']));
    }

    public function test_rejects_a_type_outside_safe_mimes_when_validating(): void
    {
        $file = UploadedFile::fake()->createWithContent('malicioso.html', '<script>alert(1)</script>');

        $result = File::addFile($file, $this->directory);

        $this->assertNull($result);
        $this->assertSame(0, File::query()->count());
    }

    public function test_does_not_leave_the_file_on_disk_when_it_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('malicioso.html', '<script>alert(1)</script>');

        File::addFile($file, $this->directory);

        $this->assertSame([], $this->filesOnDisk());
    }

    public function test_rejects_a_file_above_the_maximum_size(): void
    {
        $sizeInKb = (int) (File::MAX_FILE_SIZE / 1024) + 1024;
        $file = UploadedFile::fake()->image('enorme.jpg')->size($sizeInKb);

        $this->assertNull(File::addFile($file, $this->directory));
    }

    public function test_accepts_an_arbitrary_type_when_validation_is_disabled(): void
    {
        // El editor de contenido y los adjuntos suben lo que haga falta: la
        // validación protege los campos que esperan una imagen, no la
        // plataforma entera. Si este test se cae porque alguien "endureció" el
        // modelo, lo que se ha roto es el editor.
        $file = UploadedFile::fake()->createWithContent('modelo.stl', 'solid cube endsolid');

        $result = File::addFile($file, $this->directory, validate: false);

        $this->assertNotNull($result);
        $this->assertSame(1, File::query()->count());
    }

    public function test_caps_the_original_width_to_the_maximum(): void
    {
        $originalWidth = File::MAX_IMAGE_WIDTH + 800;
        $file = UploadedFile::fake()->image('grande.jpg', $originalWidth, 1000);

        $result = File::addFile($file, $this->directory, has_thumbnails: false);

        $this->assertNotNull($result);
        $this->assertSame(File::MAX_IMAGE_WIDTH, $result->width);
    }

    public function test_the_row_describes_the_already_processed_file(): void
    {
        $file = UploadedFile::fake()->image('grande.jpg', File::MAX_IMAGE_WIDTH + 800, 1000);

        $result = File::addFile($file, $this->directory, has_thumbnails: false);

        $this->assertNotNull($result);

        $pathOnDisk = $result->storagePathFile;
        $this->assertFileExists($pathOnDisk);

        [$realWidth, $realHeight] = getimagesize($pathOnDisk);

        $this->assertSame($realWidth, $result->width);
        $this->assertSame($realHeight, $result->height);
        $this->assertSame(filesize($pathOnDisk), $result->size);
    }

    public function test_strips_exif_metadata_from_the_stored_image(): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está disponible.');
        }

        $pathWithExif = $this->createJpegWithExifGps();

        $file = new UploadedFile($pathWithExif, 'con-gps.jpg', 'image/jpeg', null, true);

        // Comprobación de partida: si el archivo de origen no llevara EXIF, el
        // test pasaría sin demostrar nada.
        $originalExif = @exif_read_data($pathWithExif);
        $this->assertNotFalse($originalExif, 'El JPEG de partida debería llevar EXIF.');

        $result = File::addFile($file, $this->directory, has_thumbnails: false);

        $this->assertNotNull($result);

        $resultingExif = @exif_read_data($result->storagePathFile);

        if ($resultingExif !== false) {
            $this->assertArrayNotHasKey('GPSLatitude', $resultingExif);
            $this->assertArrayNotHasKey('GPSLongitude', $resultingExif);
        } else {
            $this->assertFalse($resultingExif);
        }
    }

    /**
     * El test de arriba comprueba el RESULTADO, y por sí solo no basta: al
     * reescribir una imagen el driver ya descarta los metadatos por su cuenta,
     * así que pasaría en verde aunque la limpieza se hubiera borrado del
     * código. Este comprueba la INTENCIÓN: que `stripMetadata()` deja
     * efectivamente la instancia sin EXIF y sin perfil.
     *
     * Los dos juntos son lo que sostiene la decisión de limpiar siempre y de
     * forma explícita: si mañana el driver deja de limpiar por su cuenta, el
     * primero avisa; si alguien quita la llamada por redundante, avisa el
     * segundo.
     */
    public function test_strip_metadata_leaves_the_image_without_exif(): void
    {
        $image = Image::decodePath($this->createJpegWithExifGps());

        $this->assertGreaterThan(0, $image->exif()->count(), 'La imagen de partida debería traer EXIF.');

        $method = new \ReflectionMethod(File::class, 'stripMetadata');
        $method->invoke(null, $image);

        $this->assertSame(0, $image->exif()->count());
        $this->assertNull($image->profile ?? null);
    }

    public function test_rejects_a_base64_string_above_the_maximum_size(): void
    {
        $string = 'data:image/jpeg;base64,'.str_repeat('A', File::MAX_FILE_SIZE + 1024);

        $result = File::addFileFromBase64($string, $this->directory);

        $this->assertNull($result);
        $this->assertSame(0, File::query()->count());
    }

    /**
     * Genera un JPEG real con un bloque EXIF que incluye coordenadas GPS.
     */
    private function createJpegWithExifGps(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'exif').'.jpg';

        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        // Se inyecta un APP1/Exif mínimo con GPSLatitude y GPSLongitude. Se
        // construye a mano porque GD no escribe EXIF.
        $content = file_get_contents($path);
        $exifBlock = $this->exifBlockWithGps();
        $content = substr($content, 0, 2).$exifBlock.substr($content, 2);
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Segmento APP1 con un IFD GPS mínimo (latitud y longitud).
     */
    private function exifBlockWithGps(): string
    {
        // TIFF header little-endian, IFD0 con un único puntero al IFD GPS.
        $tiff = "II\x2A\x00\x08\x00\x00\x00";

        // IFD0: 1 entrada → GPSInfoIFDPointer (0x8825), LONG, count 1.
        $tiff .= pack('v', 1);
        $tiff .= pack('vvVV', 0x8825, 4, 1, 26);
        $tiff .= pack('V', 0);

        // IFD GPS en el offset 26: 2 entradas (latitud y longitud), cada una
        // RATIONAL x3 apuntando a los datos del final.
        $gps = pack('v', 2);
        $gps .= pack('vvVV', 0x0002, 5, 3, 26 + 2 + 24 + 4);
        $gps .= pack('vvVV', 0x0004, 5, 3, 26 + 2 + 24 + 4 + 24);
        $gps .= pack('V', 0);

        // 36° 44' 30" N / 6° 25' 40" O, en RATIONAL (numerador/denominador).
        $data = pack('VVVVVV', 36, 1, 44, 1, 30, 1);
        $data .= pack('VVVVVV', 6, 1, 25, 1, 40, 1);

        $payload = "Exif\x00\x00".$tiff.$gps.$data;

        return "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    // ─── /file/resize ───

    /**
     * La ruta de redimensionado devolvía SIEMPRE la imagen genérica "no es una
     * imagen", para cualquier fichero: comprobaba `$file->type`, que no existe
     * en `File` ni como columna ni como accessor, así que `null !== 'image'`
     * era siempre cierto y no llegaba nunca a redimensionar.
     *
     * PHPStan lo señalaba y estaba silenciado en el baseline.
     */
    public function test_resize_returns_the_image_and_not_the_not_an_image_placeholder(): void
    {
        $uploaded = UploadedFile::fake()->image('foto.jpg', 1200, 800);
        $file = File::addFile($uploaded, $this->directory, is_private: false);

        $this->assertNotNull($file);

        $width = File::$thumbnailsSizeWidth['small'];

        $response = $this->get("/file/resize/{$file->module}/{$file->id}/{$width}/foto");

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('Content-Type'));

        // El marcador de "no es una imagen" se sirve desde public/images; si la
        // respuesta fuera ese fichero, el ancho no coincidiría con el pedido.
        $content = $response->streamedContent() ?: $response->getContent();
        $tmp = tempnam(sys_get_temp_dir(), 'resize');
        file_put_contents($tmp, $content);
        [$servedWidth] = getimagesize($tmp) ?: [null];
        @unlink($tmp);

        $this->assertSame($width, $servedWidth);
    }

    public function test_resize_ignores_a_width_outside_the_catalog(): void
    {
        $uploaded = UploadedFile::fake()->image('foto.jpg', 1200, 800);
        $file = File::addFile($uploaded, $this->directory, is_private: false);

        // 7 px no está en el catálogo y es menor que el más pequeño: no hay
        // nada que servir.
        $response = $this->get("/file/resize/{$file->module}/{$file->id}/7/foto");

        $response->assertOk();
    }
}
