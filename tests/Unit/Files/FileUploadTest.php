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
    private string $directorio = 'pruebas-subida';

    protected function setUp(): void
    {
        parent::setUp();

        // No se usa Storage::fake(): el modelo resuelve las rutas con
        // storage_path() —ver File::getStoragePathFileAttribute()—, así que un
        // disco falso dejaría los archivos en un sitio y el modelo los buscaría
        // en otro. Se trabaja sobre el disco real en un directorio propio y se
        // limpia al terminar.
        $this->limpiarDirectorio();
    }

    protected function tearDown(): void
    {
        $this->limpiarDirectorio();

        parent::tearDown();
    }

    private function limpiarDirectorio(): void
    {
        foreach (['private', 'public'] as $ambito) {
            $ruta = storage_path('app/'.$ambito.'/'.$this->directorio);

            if (is_dir($ruta)) {
                exec('rm -rf '.escapeshellarg($ruta));
            }
        }
    }

    /** Archivos que hay ahora mismo en el directorio de trabajo. */
    private function archivosEnDisco(): array
    {
        $ruta = storage_path('app/private/'.$this->directorio);

        if (! is_dir($ruta)) {
            return [];
        }

        return array_values(array_diff(scandir($ruta), ['.', '..']));
    }

    public function test_rechaza_un_tipo_fuera_de_safe_mimes_cuando_valida(): void
    {
        $archivo = UploadedFile::fake()->createWithContent('malicioso.html', '<script>alert(1)</script>');

        $resultado = File::addFile($archivo, $this->directorio);

        $this->assertNull($resultado);
        $this->assertSame(0, File::query()->count());
    }

    public function test_no_deja_el_archivo_en_disco_cuando_lo_rechaza(): void
    {
        $archivo = UploadedFile::fake()->createWithContent('malicioso.html', '<script>alert(1)</script>');

        File::addFile($archivo, $this->directorio);

        $this->assertSame([], $this->archivosEnDisco());
    }

    public function test_rechaza_un_archivo_por_encima_del_tamano_maximo(): void
    {
        $tamanoEnKb = (int) (File::MAX_FILE_SIZE / 1024) + 1024;
        $archivo = UploadedFile::fake()->image('enorme.jpg')->size($tamanoEnKb);

        $this->assertNull(File::addFile($archivo, $this->directorio));
    }

    public function test_acepta_un_tipo_arbitrario_cuando_la_validacion_esta_desactivada(): void
    {
        // El editor de contenido y los adjuntos suben lo que haga falta: la
        // validación protege los campos que esperan una imagen, no la
        // plataforma entera. Si este test se cae porque alguien "endureció" el
        // modelo, lo que se ha roto es el editor.
        $archivo = UploadedFile::fake()->createWithContent('modelo.stl', 'solid cube endsolid');

        $resultado = File::addFile($archivo, $this->directorio, validate: false);

        $this->assertNotNull($resultado);
        $this->assertSame(1, File::query()->count());
    }

    public function test_acota_el_ancho_del_original_al_maximo(): void
    {
        $anchoOriginal = File::MAX_IMAGE_WIDTH + 800;
        $archivo = UploadedFile::fake()->image('grande.jpg', $anchoOriginal, 1000);

        $file = File::addFile($archivo, $this->directorio, has_thumbnails: false);

        $this->assertNotNull($file);
        $this->assertSame(File::MAX_IMAGE_WIDTH, $file->width);
    }

    public function test_la_fila_describe_el_archivo_ya_procesado(): void
    {
        $archivo = UploadedFile::fake()->image('grande.jpg', File::MAX_IMAGE_WIDTH + 800, 1000);

        $file = File::addFile($archivo, $this->directorio, has_thumbnails: false);

        $this->assertNotNull($file);

        $rutaEnDisco = $file->storagePathFile;
        $this->assertFileExists($rutaEnDisco);

        [$anchoReal, $altoReal] = getimagesize($rutaEnDisco);

        $this->assertSame($anchoReal, $file->width);
        $this->assertSame($altoReal, $file->height);
        $this->assertSame(filesize($rutaEnDisco), $file->size);
    }

    public function test_elimina_los_metadatos_exif_de_la_imagen_almacenada(): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está disponible.');
        }

        $rutaConExif = $this->crearJpegConExifGps();

        $archivo = new UploadedFile($rutaConExif, 'con-gps.jpg', 'image/jpeg', null, true);

        // Comprobación de partida: si el archivo de origen no llevara EXIF, el
        // test pasaría sin demostrar nada.
        $exifOriginal = @exif_read_data($rutaConExif);
        $this->assertNotFalse($exifOriginal, 'El JPEG de partida debería llevar EXIF.');

        $file = File::addFile($archivo, $this->directorio, has_thumbnails: false);

        $this->assertNotNull($file);

        $exifResultante = @exif_read_data($file->storagePathFile);

        if ($exifResultante !== false) {
            $this->assertArrayNotHasKey('GPSLatitude', $exifResultante);
            $this->assertArrayNotHasKey('GPSLongitude', $exifResultante);
        } else {
            $this->assertFalse($exifResultante);
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
    public function test_strip_metadata_deja_la_imagen_sin_exif(): void
    {
        $imagen = Image::decodePath($this->crearJpegConExifGps());

        $this->assertGreaterThan(0, $imagen->exif()->count(), 'La imagen de partida debería traer EXIF.');

        $metodo = new \ReflectionMethod(File::class, 'stripMetadata');
        $metodo->invoke(null, $imagen);

        $this->assertSame(0, $imagen->exif()->count());
        $this->assertNull($imagen->profile ?? null);
    }

    public function test_rechaza_un_base64_por_encima_del_tamano_maximo(): void
    {
        $cadena = 'data:image/jpeg;base64,'.str_repeat('A', File::MAX_FILE_SIZE + 1024);

        $resultado = File::addFileFromBase64($cadena, $this->directorio);

        $this->assertNull($resultado);
        $this->assertSame(0, File::query()->count());
    }

    /**
     * Genera un JPEG real con un bloque EXIF que incluye coordenadas GPS.
     */
    private function crearJpegConExifGps(): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'exif').'.jpg';

        $imagen = imagecreatetruecolor(100, 100);
        imagejpeg($imagen, $ruta, 90);
        imagedestroy($imagen);

        // Se inyecta un APP1/Exif mínimo con GPSLatitude y GPSLongitude. Se
        // construye a mano porque GD no escribe EXIF.
        $contenido = file_get_contents($ruta);
        $exif = $this->bloqueExifConGps();
        $contenido = substr($contenido, 0, 2).$exif.substr($contenido, 2);
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    /**
     * Segmento APP1 con un IFD GPS mínimo (latitud y longitud).
     */
    private function bloqueExifConGps(): string
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
        $datos = pack('VVVVVV', 36, 1, 44, 1, 30, 1);
        $datos .= pack('VVVVVV', 6, 1, 25, 1, 40, 1);

        $payload = "Exif\x00\x00".$tiff.$gps.$datos;

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
    public function test_resize_devuelve_la_imagen_y_no_el_marcador_de_no_es_imagen(): void
    {
        $archivo = UploadedFile::fake()->image('foto.jpg', 1200, 800);
        $file = File::addFile($archivo, $this->directorio, is_private: false);

        $this->assertNotNull($file);

        $ancho = File::$thumbnailsSizeWidth['small'];

        $response = $this->get("/file/resize/{$file->module}/{$file->id}/{$ancho}/foto");

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('Content-Type'));

        // El marcador de "no es una imagen" se sirve desde public/images; si la
        // respuesta fuera ese fichero, el ancho no coincidiría con el pedido.
        $contenido = $response->streamedContent() ?: $response->getContent();
        $tmp = tempnam(sys_get_temp_dir(), 'resize');
        file_put_contents($tmp, $contenido);
        [$anchoServido] = getimagesize($tmp) ?: [null];
        @unlink($tmp);

        $this->assertSame($ancho, $anchoServido);
    }

    public function test_resize_ignora_un_ancho_fuera_del_catalogo(): void
    {
        $archivo = UploadedFile::fake()->image('foto.jpg', 1200, 800);
        $file = File::addFile($archivo, $this->directorio, is_private: false);

        // 7 px no está en el catálogo y es menor que el más pequeño: no hay
        // nada que servir.
        $response = $this->get("/file/resize/{$file->module}/{$file->id}/7/foto");

        $response->assertOk();
    }
}
