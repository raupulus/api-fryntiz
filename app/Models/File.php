<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use function array_filter;
use function asset;
use function auth;
use function count;
use function dd;
use function explode;
use function file_exists;
use function getimagesize;
use function storage_path;

/**
 * Class File
 */
class File extends Model
{
    protected $table = 'files';
    protected $guarded = [
        'id'
    ];

    public static $thumbnailsSizeWidth = [
        'micro' => 50,
        'small' => 100,
        'medium' => 160,
        'large' => 320,
        'xlarge' => 640,
        'xxlarge' => 1280
    ];

    /**
     * Relación con las miniaturas asociadas a un archivo de tipo imagen.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function thumbnails()
    {
        return $this->hasMany(FileThumbnail::class, 'file_id', 'id');
    }

    /**
     * Relación con el tipo de archivo asociado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fileType()
    {
        return $this->belongsTo(FileType::class, 'file_type_id', 'id');
    }

    /**
     * Devuelve la ruta hacia la imagen.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        if ($this->path && $this->name) {
            //TODO → Mirar si mejor entrar por ID o generar token random?
            // DEberá haber un controlador genérico para las imágenes y así
            // no tener que gestionar parte privada/pública. Excepto si tiene
            // roles obligados. Replantear si tiene sentido por la ruta
            // /a/b/c..../file.png creo que mejor pillar id y marcar algo
            // como el "role" de la imagen.

            if ($this->is_private) {
                // TODO → usar controlador
            } else {
                // TODO → Usar directorio público
            }

            return asset('storage/' . $this->path . '/' . $this->name);
        }

        return '';
    }

    /**
     * Devuelve la ruta hacia la imagen dentro del sistema de archivos.
     *
     * @return string
     */
    public function getStoragePathFileAttribute()
    {
        if ($this->storage_path) {
            return storage_path('app/' . $this->storage_path . '/' . $this->name);
        }

        return '';
    }

    /**
     * Almacena y devuelve un archivo recibiendo el objeto de tipo UploadFile.
     * Lo devuelve una vez almacenado.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string                        $path      Directorio dónde
     *                                                 guardarlo.
     * @param int|null                      $file_id   Id del archivo si
     *                                                 existiera.
     * @param string                        $privacity Visibilidad,
     *                                                 directorio public o
     *                                                 private
     *
     * @return \App\Models\File|null
     */
    public static function addFile(UploadedFile $uploadedFile,
                                   string       $path = 'upload',
                                   bool       $is_private = true,
                                   int          $file_id = null,
                                   bool $has_thumbnails = true
        )
    {

        $fullPath = ($is_private ? 'private' : 'public') . '/' . $path;

        $imageFullPath = $uploadedFile->store($fullPath);
        $imageNameArray = explode('/', $imageFullPath);
        $imageName = $imageNameArray[count($imageNameArray) - 1];

        ## Obtengo el tipo de archivo o lo creo si no existe.
        $fileType = FileType::updateOrCreate([
            'mime' => $uploadedFile->getClientMimeType(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
        ], [
            'user_id' => auth()->id(),
            'type' => explode('/', $uploadedFile->getClientMimeType())[0],
            'mime' => $uploadedFile->getClientMimeType(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
        ]);

        ## Cuando se está reemplazando un archivo se borra del disco el anterior.
        if ($file_id) {
            $oldFile = File::find($file_id);

            if ($oldFile && file_exists($oldFile->storagePathFile)) {
                unlink($oldFile->storagePathFile);
            }
        }


        $width = $height = null;

        if (($fileType->type === 'image') && isset(getimagesize($uploadedFile)[0])) {
            $width = getimagesize($uploadedFile)[0];
        }

        if (($fileType->type === 'image') && isset(getimagesize($uploadedFile)[1])) {
            $height = getimagesize($uploadedFile)[1];
        }

        ## Registro el archivo.
        $file = self::updateOrCreate([
            'id' => $file_id
        ], [
            'user_id' => auth()->id(),
            'file_type_id' => $fileType->id,
            'size' => $uploadedFile->getSize(),
            'width' => $width,
            'height' => $height,
            'name' => $imageName,
            'original_name' => $uploadedFile->getClientOriginalName(),
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



    public static function createThumbnails(File $file)
    {
        $sizes = self::$thumbnailsSizeWidth;

        $thumbnails = [];

        $img = Image::make($file->storagePathFile);

        $oldThumbnails = $file->thumbnails;

        ## Borro las miniaturas antiguas.
        foreach ($oldThumbnails as $oldThumbnail) {
            if (file_exists($oldThumbnail->storagePathFile)) {
                unlink($oldThumbnail->storagePathFile);
            }

            $oldThumbnail->delete();
        }

        ## Genero las nuevas miniaturas.
        foreach ($sizes as $key => $size) {

            if ($file->width > $size) {

                $newPath = storage_path('app/' . $file->storage_path . '/' . $size);

                $img->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if( ! \File::isDirectory($newPath) ) {
                    \File::makeDirectory($newPath, 493, true);
                }

                // TODO → Convertir siempre a webp y asociar a este file_type_id

                $img->save($newPath . '/' . $file->name, 60);
                //$img->save($newPath . '/' . $file->name, 70, 'webp');

                $thumbnails[] = FileThumbnail::create([
                    'file_id' => $file->id,
                    'file_type_id' => $file->file_type_id,
                    'path' => $file->path . '/' . $size,
                    'storage_path' => $file->storage_path . '/' . $size,
                    'name' => $file->name,
                    'key' => $key,
                    'width' => $img->width(),
                    'height' => $img->height(),
                    'size' => $img->filesize(),
                ]);
            }
        }

        return array_filter($thumbnails);
    }
}
