<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Intervention\Image\Facades\Image;
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
 */
class File extends Model
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
        'id'
    ];

    /**
     * Relación con el tipo de archivo asociado.
     *
     * @return BelongsTo
     */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'file_type_id', 'id');
    }

    /**
     * Devuelve la url de una imagen genérica.
     *
     * @param $size
     *
     * @return string
     */
    public static function urlDefaultImage($size = 'medium'): string
    {
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

        return asset('images/default/' . $name);
    }

    /**
     * Almacena y devuelve un archivo recibiendo el objeto de tipo UploadFile.
     * Lo devuelve una vez almacenado.
     *
     * @param UploadedFile $uploadedFile
     * @param string $path Directorio dónde guardarlo.
     * @param bool $is_private Si es privado o público.
     * @param int|null $file_id Id del archivo si existiera.
     * @param bool $has_thumbnails Si tiene miniaturas.
     *
     * @return File|null
     */
    public static function addFile(UploadedFile $uploadedFile,
                                   string       $path = 'upload',
                                   bool         $is_private = true,
                                   int          $file_id = null,
                                   bool         $has_thumbnails = true
    ): ?File
    {

        $fullPath = ($is_private ? 'private' : 'public') . '/' . $path;

        $imageFullPath = $uploadedFile->store($fullPath);
        $imageNameArray = explode('/', $imageFullPath);
        $imageName = $imageNameArray[count($imageNameArray) - 1];
        $mime = $uploadedFile->getClientMimeType();

        $canEditImage = in_array($mime, self::$imageMimeCanEdit);

        ## Obtengo el tipo de archivo o lo creo si no existe.
        $fileType = FileType::addFileType($uploadedFile->getClientMimeType(), $uploadedFile->getClientOriginalExtension());

        ## Cuando se está reemplazando un archivo se borra del disco el anterior.
        if ($file_id) {
            $oldFile = self::find($file_id);

            if ($oldFile && file_exists($oldFile->storagePathFile)) {
                unlink($oldFile->storagePathFile);
            }
        }

        ## Redimensiono la imagen si supera el ancho máximo lógico para web.
        if ($canEditImage) {
            // TODO → Máximo tamaño de archivo original si es imagen 2560x1800px
            // TODO → Borrar o cambiar metadatos de los archivos si es privado.
        }

        $width = $height = null;

        if (($fileType?->type === 'image') && isset(getimagesize($uploadedFile)[0])) {
            $width = getimagesize($uploadedFile)[0];
        }

        if (($fileType?->type === 'image') && isset(getimagesize($uploadedFile)[1])) {
            $height = getimagesize($uploadedFile)[1];
        }

        $module = explode('/', $path);
        $module = count($module) ? $module[0] : 'uploads';

        ## Registro el archivo.
        $file = self::updateOrCreate([
            'id' => $file_id
        ], [
            'user_id' => auth()->id(),
            'file_type_id' => $fileType?->id,
            'size' => $uploadedFile->getSize(),
            'width' => $width,
            'height' => $height,
            'name' => $imageName,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'module' => $module,
            'path' => $path,
            'storage_path' => $fullPath,
            'alt' => $uploadedFile->getClientOriginalName(),
            'title' => $uploadedFile->getClientOriginalName(),
            'is_private' => $is_private,
        ]);


        ## Registro las miniaturas.
        if ($has_thumbnails && $file && $file->fileType && ($file->fileType->type === 'image')) {
            $thumbnails = self::createThumbnails($file);
        }

        return $file;
    }


    /**
     * Recibe un string en base64 y lo convierte en un archivo.
     *
     * @param string $base64 Cadena en base64
     * @param string $path Directorio dónde se almacenará
     * @param bool $is_private Indica si pertenece al espacio privado
     * @param int|null $file_id Id del archivo si existiera
     * @param bool $has_thumbnails Indica si se deben generar miniaturas
     *
     * @return File|null
     */
    public static function addFileFromBase64(string $base64,
                                             string $path = 'upload',
                                             bool   $is_private = true,
                                             int    $file_id = null,
                                             bool   $has_thumbnails = true): ?File
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
     *
     * @param File $file
     *
     * @return array
     */
    public static function createThumbnails(File $file): array
    {
        $sizes = self::$thumbnailsSizeWidth;

        $thumbnails = [];
        $oldThumbnails = $file->thumbnails;

        $module = explode('/', $file->path);
        $module = count($module) ? $module[0] : 'uploads';

        ## Borro las miniaturas antiguas.
        foreach ($oldThumbnails as $oldThumbnail) {
            if (file_exists($oldThumbnail->storagePathFile)) {
                unlink($oldThumbnail->storagePathFile);
            }

            $oldThumbnail->delete();
        }

        $canEditImage = $file->fileType && in_array($file->fileType?->mime, self::$imageMimeCanEdit);

        ## Compruebo si es una imagen editable
        if (!$canEditImage) {
            return $thumbnails;
        }

        $imgOriginal = Image::make($file->storagePathFile);


        ## Genero las nuevas miniaturas.
        foreach ($sizes as $key => $size) {

            if ($file->width > $size) {

                $newPath = storage_path('app/' . $file->storage_path . '/' . $size);

                $img = clone($imgOriginal);

                $img->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (!\File::isDirectory($newPath)) {
                    \File::makeDirectory($newPath, 493, true);
                }

                // TODO → Añadir metadatos EXIF

                $extension = $file->fileType->extension;

                if ($file->fileType->mime === 'image/jpeg') {
                    $newName = preg_replace('/\.jpeg$/i', '.webp', $file->name);
                    $newName = preg_replace('/\.jpg$/i', '.webp', $file->name);
                    $img->save($newPath . '/' . $newName, 80, 'webp');
                    $extension = 'webp';
                } elseif ($file->fileType->mime === 'image/png') {
                    $newName = preg_replace('/\.png$/i', '.webp', $file->name);
                    $img->save($newPath . '/' . $newName, 80, 'webp');
                    $extension = 'webp';
                } else {
                    $newName = $file->name;
                    $img->save($newPath . '/' . $newName, 80);
                }

                ## Busco de nuevo el tipo mime, por si hubiera cambiado a webp.
                $mime = $img->mime();

                if ($mime) {
                    ## Obtengo el tipo de archivo o lo creo si no existe.
                    $fileType = FileType::addFileType($mime, $extension);
                } else {
                    $fileType = $file->fileType;
                }

                $thumbnails[] = FileThumbnail::create([
                    'file_id' => $file->id,
                    'file_type_id' => $fileType->id,
                    'module' => $module,
                    'path' => $file->path . '/' . $size,
                    'storage_path' => $file->storage_path . '/' . $size,
                    'name' => $newName,
                    'key' => $key,
                    'width' => $img->width(),
                    'height' => $img->height(),
                    'size' => $img->filesize(),
                ]);
            }
        }

        return array_filter($thumbnails);
    }

    /**
     * Procesa el borrado por lote de un conjunto de archivos.
     *
     * @param array $ids Ids de los archivos a borrar.
     *
     * @return array[int]
     */
    public static function safeDeleteByIds(array $ids): array
    {
        $files = self::whereIn('id', $ids)->get();

        $result = [];

        ## Elimino cada archivo recibido.
        foreach ($files as $file) {
            $result[] = [
                'id' => $file->id,
                'success' => self::safeDeleteById($file->id)
            ];
        }

        return $result;
    }


    /**
     * Devuelve la ruta hacia la imagen.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if ($this->path && $this->name && !$this->deleted_at) {
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
     *
     * @return string
     */
    public function getStoragePathFileAttribute(): string
    {
        if ($this->storage_path) {
            return storage_path('app/' . $this->storage_path . '/' . $this->name);
        }

        return '';
    }

    /**
     * Devuelve la url de una miniatura.
     *
     * @param string $key Clave de la miniatura.
     *
     * @return string
     */
    public function thumbnail(string $key = 'small'): string
    {
        // TOFIX: En caso de no ser una imagen devuelvo la url, pero esto no tiene mucho sentido por ahora
        if ($this->fileType && !($this->fileType->type === 'image')) {
            return $this->url;
        }

        $thumbnail = $this->thumbnails()->where('key', $key)->first();

        if (!$thumbnail) {
            $pos = array_search($key, array_keys(self::$thumbnailsSizeWidth), true);


            ## En caso de pedir la imagen más grande, buscamos la más cercana en tamaño inferior.
            if ($pos === (count(self::$thumbnailsSizeWidth) - 1)) {
                $newArraySizes = array_reverse(self::$thumbnailsSizeWidth);
                unset($newArraySizes[0]);

                foreach ($newArraySizes as $idx => $size) {
                    $thumbnail = $this->thumbnails()->where('key', $idx)->first();

                    if ($thumbnail) {
                        return $thumbnail->url;
                    }
                }
            } else {  ## En caso de tener una imagen pequeña, buscamos la superior más cercana.
                $newArraySizes = array_slice(self::$thumbnailsSizeWidth, $pos, null, true);

                foreach ($newArraySizes as $idx => $size) {
                    $thumbnail = $this->thumbnails()->where('key', $idx)->first();

                    if ($thumbnail) {
                        return $thumbnail->url;
                    }
                }
            }


            $thumbnail = $this->thumbnails()->where('key', $key)->first();
        }




        if ($thumbnail) {
            return $thumbnail->url;
        }

        return self::urlDefaultImage($key);

    }

    /**
     * Relación con las miniaturas asociadas a un archivo de tipo imagen.
     *
     * @return HasMany
     */
    public function thumbnails(): HasMany
    {
        return $this->hasMany(FileThumbnail::class, 'file_id', 'id');
    }

    /**
     * Elimina de forma segura la instancia actual con todos sus datos
     * asociados como imágenes thumbnail y/o el propio archivo.
     *
     * @return bool
     */
    public function safeDelete(): bool
    {
        return self::safeDeleteById($this->id);
    }

    /**
     * Elimina un archivo.
     *
     * @param int $id Id del archivo a eliminar.
     *
     * @return bool
     */
    public static function safeDeleteById(int $id): bool
    {
        $file = self::find($id);

        if (!$file) {
            return false;
        }

        ## Elimino las miniaturas si tuviera.
        $thumbnails = $file->thumbnails;

        foreach ($thumbnails as $thumbnail) {
            if (file_exists($thumbnail->storagePathFile)) {
                unlink($thumbnail->storagePathFile);
            }

            $thumbnail->delete();
        }

        ## Borro el archivo.
        if (file_exists($file->storagePathFile)) {
            unlink($file->storagePathFile);
        }

        return $file->delete();
    }
}
