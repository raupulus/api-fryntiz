<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Traits\HasGenericImages;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Collection as ImageCollection;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

use function array_filter;
use function asset;
use function auth;
use function clearstatcache;
use function count;
use function explode;
use function file_exists;
use function filesize;
use function getimagesize;
use function in_array;
use function is_dir;
use function mb_strtolower;
use function mkdir;
use function pathinfo;
use function preg_match;
use function preg_replace;
use function route;
use function storage_path;
use function strlen;

/**
 * Class File
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $file_type_id FK al tipo de archivo
 * @property string|null $module Nombre del módulo para acceder por path
 * @property string $path Ruta que tiene la aplicación hacia el archivo, por ejemplo: users/avatar
 * @property string|null $storage_path Ruta hacia el archivo en el storage
 * @property string $name Nombre asignado de forma interna en la aplicación, por ejemplo: fg7s97hg98hjsd8gh0d0.jpg
 * @property int|null $width Ancho de la imagen
 * @property int|null $height Alto de la imagen
 * @property string|null $original_name Nombre original del archivo, el nombre que lleva al subirse
 * @property int $size Tamaño de la imagen
 * @property string $alt
 * @property string $title
 * @property bool $is_private Indica si es privado el archivo o pertenece al espacio público de la aplicación
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read FileType|null $fileType
 * @property-read string $storage_path_file
 * @property-read string $url
 * @property-read Collection<int, FileThumbnail> $thumbnails
 * @property-read int|null $thumbnails_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereAlt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereStoragePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereWidth($value)
 *
 * @mixin \Eloquent
 */
class File extends BaseModel
{
    use HasGenericImages;

    public static $thumbnailsSizeWidth = [
        'micro' => 50,
        'small' => 160,
        'medium' => 320,
        'normal' => 640,
        'large' => 1280,
    ];

    /**
     * MIME de imagen que la aplicación sabe reprocesar (rotar, escalar, generar
     * miniaturas). Decide si se PUEDE editar, no si se ACEPTA: para eso está
     * `SAFE_MIMES`.
     */
    public static $imageMimeCanEdit = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/x-windows-bmp',
        'image/x-ms-bmp',
        'image/bmp',
    ];

    /**
     * Tipos aceptados cuando la validación está activa (`$validate = true`).
     *
     * Es lo que se espera cuando un campo pide «una imagen» o «un documento»:
     * el avatar, la portada de un contenido, la foto de un producto. Los campos
     * sin expectativa de tipo —el editor de contenido, los archivos adjuntos—
     * llaman a `addFile()` con `$validate = false` y suben lo que haga falta.
     * Esta lista no es el inventario de lo que la plataforma admite; es el de lo
     * que un campo de imagen debe recibir.
     *
     * ⚠️ NO conectar esto con la tabla `file_types`. `file_types` es un catálogo
     * de metadatos (icono, extensión, tipo legible) que se rellena desde el
     * panel con toda clase de formatos —impresión 3D, vectores, software de
     * edición, documentos— y que por tanto es entrada de usuario. Usarla como
     * lista de tipos seguros sería validar el input contra el propio input.
     * Para añadir un tipo aceptado se añade AQUÍ, a mano.
     */
    public const SAFE_MIMES = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/x-windows-bmp',
        'image/x-ms-bmp',
        'image/bmp',
        'application/pdf',
    ];

    /**
     * Techo de tamaño cuando la validación está activa: 20 MB.
     *
     * No es el límite de experiencia de usuario, que lo pone cada campo de
     * Filament y es más estricto (`ImageCropperUpload` está en 4 MB). Una foto
     * de alta calidad entra grande y el cropper la deja en un megabyte o menos
     * según para qué se vaya a usar; esto sólo corta lo absurdo.
     */
    public const MAX_FILE_SIZE = 20 * 1024 * 1024;

    /**
     * Ancho máximo al que se guarda el original de una imagen.
     */
    public const MAX_IMAGE_WIDTH = 2560;

    protected $table = 'files';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    /**
     * `fileType` va siempre con el fichero.
     *
     * No es comodidad: `thumbnailModel()` —que es por donde pasa CUALQUIER
     * miniatura de la aplicación— lo primero que hace es mirar
     * `$this->fileType->type` para saber si el fichero es una imagen. Si la
     * relación no viene cargada, eso es una consulta por fila, y fuera de
     * producción `Model::preventLazyLoading()` lo convierte en excepción: la
     * página entera se cae con «Attempted to lazy load [fileType]».
     *
     * Ha pasado en `/hardware/energy`, cuyo controlador cargaba `image` pero no
     * `image.fileType`. Cargarlo aquí lo arregla para todas las vistas a la vez,
     * presentes y futuras, y cuesta una consulta por lote —no por fila—, contra
     * una tabla de seis filas.
     *
     * @var list<string>
     */
    protected $with = ['fileType'];

    protected $fillable = [
        'user_id',
        'file_type_id',
        'module',
        'path',
        'storage_path',
        'name',
        'width',
        'height',
        'original_name',
        'size',
        'alt',
        'title',
        'is_private',
    ];

    /**
     * Relación con el tipo de archivo asociado.
     */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'file_type_id', 'id');
    }

    /**
     * Devuelve la url de una imagen genérica.
     */
    public static function urlDefaultImage($size = 'medium'): string
    {

        // TODO: Cambiar imágenes por defecto usando el formato webp

        switch ($size) {
            case 'micro':
                $name = 'micro.jpg';
                break;
            case 'small':
                $name = 'small.jpg';
                break;
            case 'medium':
                $name = 'medium.jpg';
                break;
            case 'normal':
                $name = 'normal.jpg';
                break;
            case 'large':
                $name = 'large.jpg';
                break;
            default:
                $name = 'medium.jpg';
                break;
        }

        return asset('images/default/'.$name);
    }

    /**
     * Almacena y devuelve un archivo recibiendo el objeto de tipo UploadFile.
     * Lo devuelve una vez almacenado.
     *
     * La validación es opcional y viene activada. Con `$validate = true` sólo
     * se aceptan los tipos de `SAFE_MIMES` y hasta `MAX_FILE_SIZE`: es lo que
     * usan los campos que esperan una imagen o un documento concreto. Con
     * `$validate = false` entra cualquier cosa, que es lo que necesitan el
     * editor de contenido y los archivos adjuntos, donde no hay nada que
     * restringir. El parámetro va el último para que ninguna llamada existente
     * cambie de comportamiento: todas quedan validadas sin tocarlas.
     *
     * Si es una imagen editable se rota según su orientación EXIF, se acota a
     * `MAX_IMAGE_WIDTH` y se le retiran los metadatos (ver `stripMetadata()`).
     *
     * @param  string  $path  Directorio dónde guardarlo.
     * @param  bool  $is_private  Si es privado o público.
     * @param  int|null  $file_id  Id del archivo si existiera.
     * @param  bool  $has_thumbnails  Si tiene miniaturas.
     * @param  bool  $validate  Si se comprueban tipo y tamaño contra SAFE_MIMES/MAX_FILE_SIZE.
     */
    public static function addFile(UploadedFile $uploadedFile,
        string $path = 'upload',
        bool $is_private = true,
        ?int $file_id = null,
        bool $has_thumbnails = true,
        bool $validate = true
    ): ?File {

        $fullPath = ($is_private ? 'private' : 'public').'/'.$path;

        // # Capturo los metadatos ANTES de store(): para un
        // TemporaryUploadedFile de Livewire, store() mueve (no copia) el
        // archivo fuera de livewire-tmp/, y getClientMimeType() en ese
        // objeto siempre devuelve el genérico application/octet-stream
        // (Livewire nunca lo informa al construirlo), así que se usa
        // getMimeType() (lee el contenido real) con ese como fallback.
        $size = $uploadedFile->getSize();
        $mime = $uploadedFile->getMimeType() ?: $uploadedFile->getClientMimeType();
        $originalName = $uploadedFile->getClientOriginalName();
        $originalExtension = self::sanitizeExtension($uploadedFile->getClientOriginalExtension());
        [$width, $height] = @getimagesize($uploadedFile->getRealPath()) ?: [null, null];

        // # La validación va ANTES de store(): si se comprueba después, el
        // archivo rechazado ya está escrito en el disco.
        if ($validate && ! self::passesValidation($mime, $size, $originalName)) {
            return null;
        }

        $imageFullPath = $uploadedFile->store($fullPath);
        $imageNameArray = explode('/', $imageFullPath);
        $imageName = $imageNameArray[count($imageNameArray) - 1];

        $canEditImage = in_array($mime, self::$imageMimeCanEdit);

        // # Obtengo el tipo de archivo o lo creo si no existe.
        $fileType = FileType::addFileType($mime, $originalExtension);

        // # Cuando se está reemplazando un archivo se borra del disco el anterior.
        if ($file_id) {
            $oldFile = self::find($file_id);

            if ($oldFile && file_exists($oldFile->storagePathFile)) {
                unlink($oldFile->storagePathFile);
            }
        }

        // # Rotación real, acotado de ancho y limpieza de metadatos. Devuelve
        // las medidas y el tamaño del archivo ya procesado, porque cualquiera
        // de las tres operaciones los cambia y la fila debe describir el
        // archivo que hay en el disco, no el que llegó.
        if ($canEditImage) {
            $processed = self::processStoredImage(storage_path('app/'.$imageFullPath));

            if ($processed) {
                [$width, $height, $size] = $processed;
            }
        }

        if ($fileType?->type !== 'image') {
            $width = $height = null;
        }

        $module = explode('/', $path);
        $module = count($module) ? $module[0] : 'uploads';

        // # Registro el archivo.
        $file = self::updateOrCreate([
            'id' => $file_id,
        ], [
            'user_id' => auth()->id(),
            'file_type_id' => $fileType?->id,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'name' => $imageName,
            'original_name' => $originalName,
            'module' => $module,
            'path' => $path,
            'storage_path' => $fullPath,
            'alt' => $originalName,
            'title' => $originalName,
            'is_private' => $is_private,
        ]);

        // # Registro las miniaturas.
        if ($has_thumbnails && $file && $file->fileType && ($file->fileType->type === 'image')) {
            $thumbnails = self::createThumbnails($file);
        }

        return $file;
    }

    /**
     * Comprueba tipo y tamaño contra la política del modelo.
     *
     * Devuelve false y deja rastro en el log cuando algo no encaja: quien llama
     * recibe null, que es lo que `addFile()` ya declaraba poder devolver.
     */
    protected static function passesValidation(?string $mime, ?int $size, ?string $originalName): bool
    {
        if (! $mime || ! in_array($mime, self::SAFE_MIMES, true)) {
            Log::warning('File: tipo de archivo no aceptado', [
                'mime' => $mime,
                'original_name' => $originalName,
            ]);

            return false;
        }

        if ($size !== null && $size > self::MAX_FILE_SIZE) {
            Log::warning('File: archivo por encima del tamaño máximo', [
                'size' => $size,
                'max' => self::MAX_FILE_SIZE,
                'original_name' => $originalName,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Deja la extensión en algo que se pueda escribir en una ruta sin sustos.
     *
     * Se aplica SIEMPRE, se valide el tipo o no: no restringe qué se puede
     * subir, evita que un `../`, un nombre con barras o una ristra rara acaben
     * formando parte de una ruta del disco.
     */
    protected static function sanitizeExtension(?string $extension): string
    {
        $extension = mb_strtolower((string) $extension);

        return preg_match('/^[a-z0-9]{1,8}$/', $extension) === 1 ? $extension : '';
    }

    /**
     * Prepara una imagen ya almacenada: la rota, la acota y le quita los
     * metadatos.
     *
     * @return array{0: int|null, 1: int|null, 2: int|null}|null Ancho, alto y tamaño resultantes.
     */
    protected static function processStoredImage(string $absolutePath): ?array
    {
        if (! file_exists($absolutePath)) {
            return null;
        }

        try {
            $image = Image::decodePath($absolutePath);

            // # 1. Rotación real. La orientación viaja como un flag EXIF, y ese
            // flag se va con los metadatos en el paso 3: si no se rotan los
            // píxeles antes, las fotos de móvil quedan tumbadas para siempre.
            $image->orient();

            // # 2. Acotado al ancho máximo lógico para web.
            if ($image->width() > self::MAX_IMAGE_WIDTH) {
                $image->scale(width: self::MAX_IMAGE_WIDTH);
            }

            // # 3. Limpieza de metadatos. Ver stripMetadata().
            self::stripMetadata($image);

            // # Se guarda con `strip`, que es la segunda capa de lo mismo.
            $image->save($absolutePath, ...self::stripEncoderOptions($absolutePath));
        } catch (\Throwable $e) {
            Log::warning('File: no se ha podido procesar la imagen', [
                'path' => $absolutePath,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        clearstatcache(true, $absolutePath);

        [$width, $height] = @getimagesize($absolutePath) ?: [null, null];
        $size = @filesize($absolutePath);

        return [$width, $height, $size === false ? null : $size];
    }

    /**
     * Retira los metadatos de una imagen: EXIF, GPS, IPTC y perfiles.
     *
     * Es un paso propio y deliberado, no un efecto secundario. Según el driver
     * y la versión de la librería, reescribir una imagen ya descarta los
     * metadatos por su cuenta — y justamente por eso esto está aquí: esa
     * garantía es un accidente de la implementación, no una decisión del
     * proyecto. El día que se cambie de driver, o que la librería suba de
     * major, nadie va a volver a mirar esta línea y las coordenadas GPS de una
     * foto empezarían a viajar otra vez sin que nada avise.
     *
     * Se aplica a TODAS las imágenes, privadas y públicas. Una foto pública con
     * el GPS de casa dentro es el mismo problema, sólo que con más gente
     * mirándola.
     *
     * Los metadatos propios de la plataforma —autoría, datos de la web— se
     * escriben DESPUÉS y son otra cosa; ver el TODO de `createThumbnails()` y
     * `docs/future/metadatos-imagenes.md`.
     */
    protected static function stripMetadata(ImageInterface $image): void
    {
        // No hay un "borra todos los metadatos" de una pieza: se vacía el EXIF
        // y se quita el perfil ICC por separado. Se hace sobre la instancia, y
        // ADEMÁS se codifica con `strip` (ver stripEncoderOptions()). Son dos
        // capas para lo mismo a propósito: si una deja de funcionar en una
        // versión futura, la otra sigue en pie y el test de EXIF avisa.
        $image->setExif(new ImageCollection);
        $image->removeProfile();
    }

    /**
     * Opciones de guardado que fuerzan el descarte de metadatos.
     *
     * `save()` elige el encoder por la extensión del destino. JPEG y WebP
     * aceptan `strip`; el resto de formatos no lo necesitan porque no arrastran
     * EXIF, y se guardan con las opciones por defecto.
     *
     * @return array<string, mixed>
     */
    protected static function stripEncoderOptions(string $path): array
    {
        $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'webp'], true)
            ? ['strip' => true]
            : [];
    }

    /**
     * Recibe un string en base64 y lo convierte en un archivo.
     *
     * @param  string  $base64  Cadena en base64
     * @param  string  $path  Directorio dónde se almacenará
     * @param  bool  $is_private  Indica si pertenece al espacio privado
     * @param  int|null  $file_id  Id del archivo si existiera
     * @param  bool  $has_thumbnails  Indica si se deben generar miniaturas
     * @param  bool  $validate  Igual que en addFile(): tipo y tamaño contra la política del modelo.
     */
    public static function addFileFromBase64(string $base64,
        string $path = 'upload',
        bool $is_private = true,
        ?int $file_id = null,
        bool $has_thumbnails = true,
        bool $validate = true): ?File
    {
        $payload = (string) Arr::last(explode(',', $base64));

        // # El tamaño se comprueba sobre la CADENA, antes de decodificar: una
        // cadena de 500 MB no debe llegar a materializarse en memoria ni en el
        // disco sólo para descubrir después que sobraba.
        if ($validate && (int) (strlen($payload) * 3 / 4) > self::MAX_FILE_SIZE) {
            Log::warning('File: base64 por encima del tamaño máximo', [
                'approx_size' => (int) (strlen($payload) * 3 / 4),
                'max' => self::MAX_FILE_SIZE,
            ]);

            return null;
        }

        // Get file data base64 string
        $fileData = base64_decode($payload);

        // Create temp file and get its absolute path
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Save file data in file
        file_put_contents($tempFilePath, $fileData);

        $tempFileObject = new \Illuminate\Http\File($tempFilePath);

        // # El MIME real se comprueba ANTES de construir el UploadedFile, para
        // descartar el temporal cuanto antes si no encaja.
        if ($validate && ! self::passesValidation($tempFileObject->getMimeType(), $tempFileObject->getSize() ?: null, $tempFileObject->getFilename())) {
            fclose($tempFile);

            return null;
        }

        $uploadedFile = new UploadedFile(
            $tempFileObject->getPathname(),
            $tempFileObject->getFilename(),
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        $file = self::addFile($uploadedFile, $path, $is_private, $file_id, $has_thumbnails, $validate);

        // Close this file after response is sent.
        // Closing the file will cause to remove it from temp director!
        app()->terminating(function () use ($tempFile) {
            fclose($tempFile);
        });

        return $file;
    }

    /**
     * Crea las miniaturas de un archivo.
     */
    public static function createThumbnails(File $file): array
    {
        $sizes = self::$thumbnailsSizeWidth;

        $thumbnails = [];
        $oldThumbnails = $file->thumbnails;

        $module = explode('/', $file->path);
        $module = count($module) ? $module[0] : 'uploads';

        // # Borro las miniaturas antiguas.
        foreach ($oldThumbnails as $oldThumbnail) {
            if (file_exists($oldThumbnail->storagePathFile)) {
                unlink($oldThumbnail->storagePathFile);
            }

            $oldThumbnail->delete();
        }

        $canEditImage = $file->fileType && in_array($file->fileType->mime, self::$imageMimeCanEdit);

        // # Compruebo si es una imagen editable
        if (! $canEditImage) {
            return $thumbnails;
        }

        $imgOriginal = Image::decodePath($file->storagePathFile);

        // # Genero las nuevas miniaturas.
        foreach ($sizes as $key => $size) {

            if ($file->width > $size) {

                $newPath = storage_path('app/'.$file->storage_path.'/'.$size);

                $img = clone $imgOriginal;

                $img->scale(width: $size);

                if (! is_dir($newPath)) {
                    mkdir($newPath, 0755, true);
                }

                // TODO: escribir en la miniatura los metadatos DE PLATAFORMA
                // (datos de la web y autoría) más la orientación, heredando lo
                // que se decida para el original. Sigue pendiente porque no es
                // una línea: GD no escribe EXIF y la librería no expone API
                // para ello, haría falta Imagick (no instalado) o una
                // dependencia tipo lsolesen/pel; y estas miniaturas se guardan
                // en WebP, donde los metadatos van en un chunk XMP con soporte
                // pobre en PHP. Análisis y opciones en
                // docs/future/metadatos-imagenes.md.
                // Ojo: esto es lo CONTRARIO de stripMetadata(), que limpia lo
                // que trae el archivo de origen. Primero se limpia lo ajeno,
                // después se escribe lo nuestro.

                $extension = $file->fileType->extension;

                if ($file->fileType->mime === 'image/jpeg') {
                    $newName = preg_replace('/\.jpeg$/i', '.webp', $file->name);
                    $newName = preg_replace('/\.jpg$/i', '.webp', $newName);
                    $img->encode(new WebpEncoder(quality: 90, strip: true))->save($newPath.'/'.$newName);
                    $extension = 'webp';
                } elseif ($file->fileType->mime === 'image/png') {
                    $newName = preg_replace('/\.png$/i', '.webp', $file->name);
                    $img->encode(new WebpEncoder(quality: 90, strip: true))->save($newPath.'/'.$newName);
                    $extension = 'webp';
                } else {
                    $newName = $file->name;
                    $img->save($newPath.'/'.$newName, quality: 90);
                }

                // # Busco de nuevo el tipo mime, por si hubiera cambiado a webp.
                $mime = 'image/webp';

                if ($mime) {
                    // # Obtengo el tipo de archivo o lo creo si no existe.
                    $fileType = FileType::addFileType($mime, $extension);
                } else {
                    $fileType = $file->fileType;
                }

                $thumbnails[] = FileThumbnail::create([
                    'file_id' => $file->id,
                    'file_type_id' => $fileType->id,
                    'module' => $module,
                    'path' => $file->path.'/'.$size,
                    'storage_path' => $file->storage_path.'/'.$size,
                    'name' => $newName,
                    'key' => $key,
                    'width' => $img->width(),
                    'height' => $img->height(),
                    'size' => filesize($newPath.'/'.$newName),
                ]);
            }
        }

        return array_filter($thumbnails);
    }

    /**
     * Procesa el borrado por lote de un conjunto de archivos.
     *
     * @param  array  $ids  Ids de los archivos a borrar.
     * @return array[int]
     */
    public static function safeDeleteByIds(array $ids): array
    {
        $files = self::whereIn('id', $ids)->get();

        $result = [];

        // # Elimino cada archivo recibido.
        foreach ($files as $file) {
            $result[] = [
                'id' => $file->id,
                'success' => self::safeDeleteById($file->id),
            ];
        }

        return $result;
    }

    /**
     * Devuelve la ruta hacia la imagen.
     */
    public function getUrlAttribute(): string
    {
        if ($this->path && $this->name && ! $this->deleted_at) {
            return route('file.get', [
                'module' => $this->module,
                'id' => $this->id,
                'slug' => $this->name,
            ]);
        }

        return self::genericImageUrl('not_found');
    }

    /**
     * Devuelve la ruta hacia la imagen dentro del sistema de archivos.
     */
    public function getStoragePathFileAttribute(): string
    {
        if ($this->storage_path) {
            return storage_path('app/'.$this->storage_path.'/'.$this->name);
        }

        return '';
    }

    /**
     * Devuelve la url de una miniatura.
     *
     * @param  string  $key  Clave de la miniatura.
     */
    public function thumbnail(string $key = 'small'): string
    {
        $thumbnailFound = $this->thumbnailModel($key);

        return $thumbnailFound ? $thumbnailFound->url : $this->url;
    }

    /**
     * La miniatura, como modelo, para quien necesite más que su URL.
     *
     * Las etiquetas `og:image:width`, `og:image:height` y `og:image:type`
     * necesitan las dimensiones y el mime, que sólo están en la fila. Sin
     * ellas, al compartir un enlace la red social tiene que descargar la
     * imagen para saber cómo maquetar la tarjeta, y hasta entonces enseña un
     * hueco.
     *
     * Si la clave pedida no existe se cae a la mayor de las **menores**, nunca
     * a una más grande: es una miniatura, no vale servir el original de 1280 px
     * donde se pedía uno de 160.
     *
     * @param  string  $key  Clave de la miniatura (`micro`, `small`, `medium`, `normal`, `large`).
     */
    public function thumbnailModel(string $key = 'small'): ?FileThumbnail
    {
        // No es una imagen: no hay miniaturas que buscar.
        if ($this->fileType && $this->fileType->type !== 'image') {
            return null;
        }

        $keys = array_keys(self::$thumbnailsSizeWidth);
        $position = array_search($key, $keys, true);

        // Claves candidatas: la pedida y las de menor tamaño, en ese orden.
        $candidatas = $position === false
            ? [$key]
            : array_reverse(array_slice($keys, 0, $position + 1));

        // Una sola consulta. Antes era un `first()` por cada tamaño hacia
        // abajo: hasta cinco consultas para pintar una miniatura, y esto se
        // llama una vez por fila en cualquier listado con imágenes.
        $miniaturas = $this->thumbnails()
            ->whereIn('key', $candidatas)
            ->get()
            ->keyBy('key');

        foreach ($candidatas as $candidata) {
            $thumbnailFound = $miniaturas->get($candidata);

            if ($thumbnailFound instanceof FileThumbnail) {
                return $thumbnailFound;
            }
        }

        return null;
    }

    /**
     * Relación con las miniaturas asociadas a un archivo de tipo imagen.
     */
    public function thumbnails(): HasMany
    {
        return $this->hasMany(FileThumbnail::class, 'file_id', 'id');
    }

    /**
     * Elimina de forma segura la instancia actual con todos sus datos
     * asociados como imágenes thumbnail y/o el propio archivo.
     */
    public function safeDelete(): bool
    {
        return self::safeDeleteById($this->id);
    }

    /**
     * Elimina un archivo.
     *
     * @param  int  $id  Id del archivo a eliminar.
     */
    public static function safeDeleteById(int $id): bool
    {
        $file = self::find($id);

        if (! $file) {
            return false;
        }

        // # Elimino las miniaturas si tuviera.
        $thumbnails = $file->thumbnails;

        foreach ($thumbnails as $thumbnail) {
            if (file_exists($thumbnail->storagePathFile)) {
                unlink($thumbnail->storagePathFile);
            }

            $thumbnail->delete();
        }

        // # Borro el archivo.
        if (file_exists($file->storagePathFile)) {
            unlink($file->storagePathFile);
        }

        return $file->delete();
    }
}
