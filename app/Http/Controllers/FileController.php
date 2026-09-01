<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function array_filter;
use function array_values;
use function auth;
use function dirname;
use function in_array;
use function is_dir;
use function is_file;
use function max;
use function mkdir;
use function redirect;
use function response;
use function sort;
use function storage_path;

/**
 * Class FileController
 */
class FileController extends Controller
{
    /**
     * Devuelve un archivo del sistema de archivos.
     *
     * @param  string  $module  Grupo del archivo.
     * @param  int  $id  Identificador del archivo.
     * @param  string|null  $slug  Slug del archivo.
     * @return BinaryFileResponse
     */
    public function get(string $module, int $id, ?string $slug = null)
    {
        $file = File::find($id);

        if (! $file) {
            return response()->file(File::genericImagePath('not_found'));
        }

        // # Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            return response()->file(File::genericImagePath('not_authorized'));
        }

        return response()->file($file->storagePathFile);
    }

    /**
     * Redimensiona una imagen y la devuelve a ese tamaño.
     *
     * El ancho no se acepta tal cual (SEC-04): se resuelve contra los tamaños
     * que el proyecto ya genera en `File::$thumbnailsSizeWidth`. Con una lista
     * cerrada el número de variantes posibles es finito, así que la caché en
     * disco tiene sentido y un ancho enorme deja de ser una forma gratuita de
     * hacer trabajar al servidor.
     *
     * @param  string  $module  Grupo del archivo.
     * @param  int  $id  Identificador del archivo.
     * @param  string  $slug  Slug del archivo.
     * @param  int  $width  Ancho del archivo a redimensionar.
     */
    public function resizeAndGet(string $module, int $id, string $slug, int $width)
    {

        $file = File::find($id);

        if (! $file) {
            return response()->file(File::genericImagePath('not_found'));
        }

        if ($file->type !== 'image') {
            return response()->file(File::genericImagePath('not_image'));
        }

        // # Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            return response()->file(File::genericImagePath('not_authorized'));
        }

        $resolvedWidth = $this->resolveWidth($width);

        if (! $resolvedWidth) {
            return response()->file(File::genericImagePath('not_found'));
        }

        // # Si la miniatura ya existe se sirve del disco. Es el caso normal:
        // createThumbnails() escribe justo en esta ruta, así que para los
        // anchos del catálogo no hay que tocar la librería de imagen.
        $cachedPath = $this->cachedPath($file, $resolvedWidth);

        if ($cachedPath && is_file($cachedPath)) {
            return response()->file($cachedPath);
        }

        $image = Image::decodePath($file->storagePathFile);
        $image->scale(width: $resolvedWidth);

        // # Se guarda para que la siguiente petición del mismo ancho salga por
        // la rama de arriba y no vuelva a reprocesar.
        if ($cachedPath) {
            $directory = dirname($cachedPath);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $image->save($cachedPath);

            return response()->file($cachedPath);
        }

        $encoded = $image->encodeUsingMediaType($file->fileType?->mime ?: 'image/jpeg');

        return response($encoded->toString())
            ->header('Content-Type', $encoded->mediaType());
    }

    /**
     * Resuelve el ancho pedido contra el catálogo de tamaños del proyecto.
     *
     * Un ancho que no esté en la lista cae al mayor de los permitidos que no lo
     * supere; por debajo del más pequeño no hay nada que servir y devuelve null.
     */
    private function resolveWidth(int $width): ?int
    {
        if ($width < 1) {
            return null;
        }

        $allowed = array_values(File::$thumbnailsSizeWidth);
        sort($allowed);

        if (in_array($width, $allowed, true)) {
            return $width;
        }

        $candidates = array_filter($allowed, static fn (int $size): bool => $size <= $width);

        return $candidates ? max($candidates) : null;
    }

    /**
     * Ruta en disco de la variante cacheada, que es donde `createThumbnails()`
     * escribe las miniaturas.
     */
    private function cachedPath(File $file, int $width): ?string
    {
        if (! $file->storage_path || ! $file->name) {
            return null;
        }

        return storage_path('app/'.$file->storage_path.'/'.$width.'/'.$file->name);
    }

    /**
     * Descarga un archivo, con la misma comprobación de privacidad que `get()`.
     *
     * El método tenía el cuerpo vacío: `GET /file/download/...` respondía 200
     * con el cuerpo en blanco, así que el navegador guardaba un fichero de cero
     * bytes y nadie recibía un error. Un endpoint que no hace nada es peor que
     * uno que no existe, porque parece que funciona.
     *
     * `upload()` estaba igual de vacío y además enrutado en `POST /file/upload`.
     * Ese no se implementa: las subidas de v2 van por el panel, que valida tipo,
     * tamaño y propiedad, y genera las miniaturas. La ruta se retira; cuando
     * haga falta una subida por HTTP se hará entera y con su contrato.
     *
     * @param  string  $module  Grupo del archivo.
     * @param  int  $id  Identificador del archivo.
     * @param  string|null  $slug  Slug del archivo.
     */
    public function download(string $module, int $id, ?string $slug = null): BinaryFileResponse
    {
        $file = File::find($id);

        if (! $file) {
            return response()->file(File::genericImagePath('not_found'));
        }

        if ($file->is_private && ($file->user_id !== auth()->id())) {
            return response()->file(File::genericImagePath('not_authorized'));
        }

        if (! file_exists($file->storagePathFile)) {
            return response()->file(File::genericImagePath('not_found'));
        }

        // Se descarga con el nombre con el que se subió, no con el interno.
        return response()->download(
            $file->storagePathFile,
            $file->original_name ?: $file->name
        );
    }

    /**
     * Elimina un archivo.
     *
     *
     * @return void
     */
    public function delete(int $id)
    {
        // N27: no se comprobaba nada. `safeDeleteById()` borra el fichero del
        // disco, sus miniaturas y la fila — con sólo pasarle un id. Cualquiera
        // podía barrer los ficheros de cualquier usuario recorriendo ids.
        $file = File::find($id);

        if (! $file) {
            return redirect()->back()->with('message', 'El archivo no existe.');
        }

        if ((int) $file->user_id !== (int) auth()->id()) {
            abort(403, 'Ese archivo no es tuyo.');
        }

        $destroy = File::safeDeleteById($id);

        if ($destroy) {
            $message = 'Archivo eliminado correctamente.';
        } else {
            $message = 'No se ha podido eliminar el archivo.';
        }

        // TODO → Redireccionar a la página de archivos? o atrás?.

        return redirect()->back()->with('message', $message);
    }
}
