<?php

declare(strict_types=1);

namespace App\Traits;

use function asset;
use function file_exists;
use function public_path;

/**
 * Las imágenes genéricas que se devuelven cuando no se puede servir la real.
 *
 * Antes esto era un array estático duplicado en `File` y en `FileThumbnail` con
 * **rutas que no existían**: nueve de las diez entradas eran nombres sueltos
 * (`'not_authorized.png'`), sin directorio y sin fichero detrás. Y se pasaban
 * tal cual a `response()->file(...)`, que con una ruta inexistente lanza
 * `FileNotFoundException`.
 *
 * O sea: pedir un fichero privado ajeno no devolvía la imagen de «no
 * autorizado», devolvía un **500**. Igual con «no es una imagen» y con la mitad
 * de los caminos de error de los tres controladores de ficheros.
 *
 * Ahora todas las rutas son relativas a `public/`, se resuelven a ruta absoluta
 * con `public_path()` —`response()->file()` con una ruta relativa depende del
 * directorio de trabajo del proceso, que no es el mismo en web que en consola—
 * y si el fichero concreto no está, se cae a `not_found.webp`, que sí existe.
 */
trait HasGenericImages
{
    /**
     * Clave => ruta relativa a `public/`.
     *
     * @var array<string,string>
     */
    public static $genericImages = [
        'error' => 'images/default/errors/error.webp',
        'default' => 'images/default/normal.jpg',
        'not_found' => 'images/default/errors/not_found.webp',
        'not_image' => 'images/default/errors/not_image.webp',
        'not_authorized' => 'images/default/errors/not_authorized.webp',
        'not_allowed' => 'images/default/errors/not_allowed.webp',
        'not_allowed_extension' => 'images/default/errors/not_allowed_extension.webp',
        'not_allowed_size' => 'images/default/errors/not_allowed_size.webp',
        'not_allowed_type' => 'images/default/errors/not_allowed_type.webp',
        'not_available' => 'images/default/errors/not_available.webp',
    ];

    /**
     * Ruta ABSOLUTA de una imagen genérica, para `response()->file()`.
     *
     * Si el fichero de esa clave no existe se devuelve el de «no encontrado»,
     * que es el único que hay garantizado. Nunca devuelve una ruta que no
     * exista: el objetivo es que un error de fichero no se convierta en un 500.
     */
    public static function genericImagePath(string $key): string
    {
        $path = public_path(self::$genericImages[$key] ?? self::$genericImages['not_found']);

        if (file_exists($path)) {
            return $path;
        }

        $porDefecto = public_path(self::$genericImages['not_found']);

        return file_exists($porDefecto) ? $porDefecto : $path;
    }

    /**
     * URL pública de una imagen genérica, para las respuestas JSON y las vistas.
     */
    public static function genericImageUrl(string $key): string
    {
        return asset(self::$genericImages[$key] ?? self::$genericImages['not_found']);
    }
}
