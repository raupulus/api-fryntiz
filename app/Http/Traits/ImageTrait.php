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

    /**
     * Devuelve la imagen en tamaño 50px de ancho.
     *
     * @return string
     */
    public function getUrlImageMicroAttribute(): string
    {
        return $this->image?->thumbnail('micro') ?? File::urlDefaultImage('micro');
    }


    /**
     * Devuelve la imagen en tamaño 160px de ancho.
     *
     * @return string
     */    public function getUrlImageSmallAttribute(): string
    {
        return $this->image?->thumbnail('small') ?? File::urlDefaultImage('small');
    }

    /**
     * Devuelve la imagen en tamaño 320px de ancho.
     *
     * @return string
     */
    public function getUrlImageMediumAttribute(): string
    {
        return $this->image?->thumbnail('medium') ?? File::urlDefaultImage('medium');
    }

    /**
     * Devuelve la imagen en tamaño 640px de ancho.
     *
     * @return string
     */
    public function getUrlImageNormalAttribute(): string
    {
        return $this->image?->thumbnail('normal') ?? File::urlDefaultImage('normal');
    }

    /**
     * Devuelve la imagen en tamaño 1280px de ancho.
     *
     * @return string
     */
    public function getUrlImageLargeAttribute(): string
    {
        return $this->image?->thumbnail('large') ?? File::urlDefaultImage('large');
    }
}
