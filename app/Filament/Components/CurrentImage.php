<?php

declare(strict_types=1);

namespace App\Filament\Components;

use App\Models\File;
use Filament\Schemas\Components\Image;
use Illuminate\Database\Eloquent\Model;

/**
 * La imagen que el registro ya tiene guardada, para pintarla junto al uploader.
 *
 * **El agujero que tapa.** Los recursos del panel suben imágenes con
 * `ImageCropperUpload::makeImage('image_id')`, es decir, un `FileUpload` de
 * Filament apuntando a una **clave foránea** a la tabla `files`. Un
 * `FileUpload` espera una ruta dentro de un disco, y la URL real de una imagen
 * de este proyecto no lo es: {@see File::getUrlAttribute()} devuelve una ruta
 * de controlador (`route('file.get', …)`), que además es la única que sirve
 * para los ficheros privados.
 *
 * El resultado es que el estado del campo era el id numérico, el componente
 * intentaba pintar «4» como si fuera una ruta, y cada página de edición acabó
 * haciendo `unset($data['image_id'])` en `mutateFormDataBeforeFill()` para que
 * al menos no diera error. O sea: **el panel nunca ha enseñado la imagen ya
 * guardada**, sólo el hueco vacío para subir otra. En el frontend sí se ve,
 * porque allí se resuelve la relación (`$modelo->image->url`), y de ahí la
 * sensación de que las imágenes «no terminan de cargar» sólo en el panel.
 *
 * Esto lo resuelve por el lado de la lectura y **sin tocar el guardado**, que
 * funciona y está probado: se pinta la imagen actual encima del uploader, y el
 * uploader sigue sirviendo sólo para sustituirla.
 *
 * Uso:
 *
 *     CurrentImage::deLaRelacion(),                    // relación `image`
 *     CurrentImage::deLaRelacion('portada', 'Portada actual'),
 *     ImageCropperUpload::makeImage('image_id')->…
 */
class CurrentImage extends Image
{
    /**
     * @param  string  $relacion  Relación del modelo que devuelve el `File`.
     * @param  string  $alt  Texto alternativo de la imagen.
     */
    public static function deLaRelacion(string $relacion = 'image', string $alt = 'Imagen actual'): static
    {
        return static::make(
            static fn (?Model $record): string => self::urlDe($record, $relacion),
            $alt,
        )
            // Sin registro (formulario de creación) o sin imagen no hay nada
            // que enseñar, y un hueco con la imagen de «no encontrado» sería
            // peor que no pintar nada.
            ->visible(static fn (?Model $record): bool => self::ficheroDe($record, $relacion) instanceof File)
            ->imageHeight('12rem')
            ->columnSpanFull();
    }

    /**
     * URL de la miniatura mediana, con la imagen completa como respaldo.
     *
     * `File::thumbnail()` ya cae en `$this->url` cuando no hay miniatura de ese
     * tamaño, así que esto vale igual para las imágenes de la v1, que se
     * subieron antes de que existieran las miniaturas.
     */
    private static function urlDe(?Model $record, string $relacion): string
    {
        return self::ficheroDe($record, $relacion)?->thumbnail('medium') ?? '';
    }

    private static function ficheroDe(?Model $record, string $relacion): ?File
    {
        // Sin registro es el formulario de creación: no hay imagen previa.
        if (! $record instanceof Model || ! $record->exists) {
            return null;
        }

        // `getAttribute()` resuelve la relación por su nombre y la carga si
        // hace falta, así que sirve igual para `BelongsTo` que para `HasOne`.
        $fichero = $record->getAttribute($relacion);

        return $fichero instanceof File ? $fichero : null;
    }
}
