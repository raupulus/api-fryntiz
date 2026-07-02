<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Intervention\Image\Laravel\Facades\Image;

use function array_filter;
use function asset;
use function auth;
use function count;
use function explode;
use function file_exists;
use function getimagesize;
use function in_array;
use function preg_replace;
use function route;
use function storage_path;

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
    public static $thumbnailsSizeWidth = [
        'micro' => 50,
        'small' => 160,
        'medium' => 320,
        'normal' => 640,
        'large' => 1280,
    ];

    public static $genericImages = [
        'error' => 'error.png',
        'default' => 'default.png',
        'not_found' => 'images/default/errors/not_found.webp',
        'not_image' => 'not_image.png',  // No Es una imagen
        'not_authorized' => 'not_authorized.png',
        'not_allowed' => 'not_allowed.png',
        'not_allowed_extension' => 'not_allowed_extension.png',
        'not_allowed_size' => 'not_allowed_size.png',
        'not_allowed_type' => 'not_allowed_type.png',
        'not_available' => 'not_available.png',
    ];

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

    protected $table = 'files';

    protected $guarded = [
        'id',
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
     * @param  string  $path  Directorio dónde guardarlo.
     * @param  bool  $is_private  Si es privado o público.
     * @param  int|null  $file_id  Id del archivo si existiera.
     * @param  bool  $has_thumbnails  Si tiene miniaturas.
     */
    public static function addFile(UploadedFile $uploadedFile,
        string $path = 'upload',
        bool $is_private = true,
        ?int $file_id = null,
        bool $has_thumbnails = true
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
        $originalExtension = $uploadedFile->getClientOriginalExtension();
        [$width, $height] = @getimagesize($uploadedFile->getRealPath()) ?: [null, null];

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

        // # Redimensiono la imagen si supera el ancho máximo lógico para web.
        if ($canEditImage) {
            // TODO → Máximo tamaño de archivo original si es imagen 2560x1800px
            // TODO → Borrar o cambiar metadatos de los archivos si es privado.
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
     * Recibe un string en base64 y lo convierte en un archivo.
     *
     * @param  string  $base64  Cadena en base64
     * @param  string  $path  Directorio dónde se almacenará
     * @param  bool  $is_private  Indica si pertenece al espacio privado
     * @param  int|null  $file_id  Id del archivo si existiera
     * @param  bool  $has_thumbnails  Indica si se deben generar miniaturas
     */
    public static function addFileFromBase64(string $base64,
        string $path = 'upload',
        bool $is_private = true,
        ?int $file_id = null,
        bool $has_thumbnails = true): ?File
    {
        // Get file data base64 string
        $fileData = base64_decode(Arr::last(explode(',', $base64)));

        // Create temp file and get its absolute path
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Save file data in file
        file_put_contents($tempFilePath, $fileData);

        $tempFileObject = new \Illuminate\Http\File($tempFilePath);

        $uploadedFile = new UploadedFile(
            $tempFileObject->getPathname(),
            $tempFileObject->getFilename(),
            $tempFileObject->getMimeType(),
            0,
            true // Mark it as test, since the file isn't from real HTTP POST.
        );

        $file = self::addFile($uploadedFile, $path, $is_private, $file_id, $has_thumbnails);

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

        $canEditImage = $file->fileType && in_array($file->fileType?->mime, self::$imageMimeCanEdit);

        // # Compruebo si es una imagen editable
        if (! $canEditImage) {
            return $thumbnails;
        }

        $imgOriginal = Image::read($file->storagePathFile);

        // # Genero las nuevas miniaturas.
        foreach ($sizes as $key => $size) {

            if ($file->width > $size) {

                $newPath = storage_path('app/'.$file->storage_path.'/'.$size);

                $img = clone $imgOriginal;

                $img->scale(width: $size);

                if (! \File::isDirectory($newPath)) {
                    \File::makeDirectory($newPath, 493, true);
                }

                // TODO: Anadir metadatos EXIF

                $extension = $file->fileType->extension;

                if ($file->fileType->mime === 'image/jpeg') {
                    $newName = preg_replace('/\.jpeg$/i', '.webp', $file->name);
                    $newName = preg_replace('/\.jpg$/i', '.webp', $newName);
                    $img->toWebp(90)->save($newPath.'/'.$newName);
                    $extension = 'webp';
                } elseif ($file->fileType->mime === 'image/png') {
                    $newName = preg_replace('/\.png$/i', '.webp', $file->name);
                    $img->toWebp(90)->save($newPath.'/'.$newName);
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

        return asset(self::$genericImages['not_found']);
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
        // # En caso de no ser una imagen, devuelvo la url del archivo directamente.
        if ($this->fileType && ! ($this->fileType->type === 'image')) {
            return $this->url;
        }

        // # Obtenemos la miniatura en base a la clave.
        $thumbnail = $this->thumbnails()->where('key', $key)->first();

        // # Si no encontramos la miniatura, buscamos iterativamente la miniatura de menor tamaño
        if (! $thumbnail) {
            $keys = array_keys(self::$thumbnailsSizeWidth);
            $pos = array_search($key, $keys, true);

            // Iteramos hacia abajo para encontrar una miniatura existente
            for ($i = $pos; $i >= 0; $i--) {
                $thumbnail = $this->thumbnails()->where('key', $keys[$i])->first();
                if ($thumbnail) {
                    return $thumbnail->url;
                }
            }
        }

        // # Si encontramos la miniatura, devolvemos su URL.
        if ($thumbnail) {
            return $thumbnail->url;
        }

        // # Si no se ha encontrado ninguna miniatura, devolvemos la URL de la imagen principal.
        return $this->url;
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
