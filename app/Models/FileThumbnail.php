<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use function route;
use function storage_path;

/**
 * Class FileThumbnail
 */
class FileThumbnail extends Model
{
    protected $table = 'file_thumbnails';
    protected $guarded = [
        'id'
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

    /**
     * Devuelve la ruta hacia la imagen.
     *
     * @return string
     */
    public function getUrlAttribute()
    {

        if ($this->path && $this->name && !$this->deleted_at) {
            return route('file.thumbnail.get', [
                'module' => $this->module,
                'id' => $this->id,
                'slug' => $this->name,
            ]);
        }

        return self::$genericImages['not_found'];
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
}
