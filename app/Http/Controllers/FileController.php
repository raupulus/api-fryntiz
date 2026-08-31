<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function auth;
use function redirect;
use function response;

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
     * @param  string  $module  Grupo del archivo.
     * @param  int  $id  Identificador del archivo.
     * @param  string  $slug  Slug del archivo.
     * @param  int  $width  Ancho del archivo a redimensionar.
     */
    public function resizeAndGet(string $module, int $id, string $slug, int $width)
    {

        $file = File::find($id);

        if (! $file) {
            // TODO → Resize this file.
            return response()->file(File::genericImagePath('not_found'));
        }

        // TODO → Check if file is an image.

        if ($file->type !== 'image') {
            // TODO → Resize this file.
            return response()->file(File::genericImagePath('not_image'));
        }

        // # Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            // TODO → Resize this file.
            return response()->file(File::genericImagePath('not_authorized'));
        }

        $image = Image::read($file->storagePathFile);
        $image->scale(width: (int) $width);

        // TODO → Cachear la imagen o comprobar si ya existe ahí.

        $encoded = $image->encodeByMediaType();

        return response($encoded->toString())
            ->header('Content-Type', $encoded->mediaType());
        // return response()->file($file->storagePathFile)->deleteFileAfterSend();

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
