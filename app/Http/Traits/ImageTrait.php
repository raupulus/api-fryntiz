<?php
namespace App\Http\Traits;

use App\Models\File;

/**
 * Trait ImageTrait
 * @package App\Http\Traits
 */
trait ImageTrait
{
    /**
     * Devuelve la ruta hacia la foto asociada al curriculum.
     *
     * @return string
     */
    public function getUrlImageAttribute(): string
    {
        return $this->image ? $this->image->url : File::urlDefaultImage('large');
    }

    /**
     * Devuelve el thumbnail de la imagen asociada.
     *
     * @param string $size Clave con el tamaño del thumbnail, o se devuelve por defecto.
     *
     * @return string
     */
    public function urlThumbnail(string $size = 'medium'): string
    {
        if ($this->image) {
            return $this->image->thumbnail($size);
        }

        return File::urlDefaultImage($size);
    }
}
