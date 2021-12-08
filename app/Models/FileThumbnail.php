<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * Devuelve la ruta hacia la imagen dentro del sistema de archivos.
     *
     * @return string
     */
    public function getStoragePathFileAttribute()
    {
        if ($this->storage_path) {
            return storage_path($this->storage_path . '/' . $this->name);
        }

        return '';
    }
}
