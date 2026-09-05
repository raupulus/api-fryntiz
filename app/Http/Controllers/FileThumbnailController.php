<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FileThumbnail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function auth;
use function is_file;
use function response;

/**
 * Class FileThumbnailController
 */
class FileThumbnailController extends Controller
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
        $thumbnail = FileThumbnail::with('file')->find($id);

        if (! $thumbnail) {
            return $this->missing();
        }

        // N175: `file_thumbnails` no tiene `is_private` ni `user_id`. Se
        // comprobaban sobre la propia miniatura, así que siempre eran `null` y
        // la condición nunca se cumplía: **las miniaturas de los ficheros
        // privados se servían a cualquiera, sin autenticar**. Y `large` son
        // 1280 px, o sea que para una foto la miniatura *es* la foto.
        //
        // La privacidad vive en el fichero padre. Si la miniatura ha quedado
        // huérfana no se sabe de quién es: no se sirve.
        $file = $thumbnail->file;

        if (! $file) {
            return $this->missing();
        }

        if ($file->is_private && (int) $file->user_id !== (int) auth()->id()) {
            return response()->file(FileThumbnail::genericImagePath('not_authorized'));
        }

        return $this->serve($thumbnail);
    }

    /**
     * Sirve la miniatura comprobando antes que el fichero existe en disco.
     *
     * `response()->file()` sobre una ruta que no existe lanza
     * `FileNotFoundException`, y aquí nadie la capturaba: una miniatura
     * registrada en base de datos cuyo fichero no esté en el disco del servidor
     * —storage sin migrar, sincronización a medias, una caché borrada a mano—
     * devolvía un **500 público** en una ruta que sirve las ilustraciones de
     * ocho webs (AR-ERR-01).
     *
     * Es el mismo fallo que ya se corrigió en `FileController::serve()`, y se
     * resuelve igual: marcador de «no encontrado» con un 404 de verdad, para que
     * la maqueta aguante y ni el cliente ni la caché se queden un 200 sobre algo
     * que no existe.
     */
    private function serve(FileThumbnail $thumbnail): BinaryFileResponse
    {
        $path = (string) $thumbnail->storagePathFile;

        if ($path === '' || ! is_file($path)) {
            return $this->missing();
        }

        return response()->file($path);
    }

    /**
     * Imagen genérica de «no encontrado», con el código HTTP correcto.
     *
     * `response()->file()` no admite código de estado —su firma es
     * `file($file, array $headers = [])`—, así que se ajusta después.
     */
    private function missing(): BinaryFileResponse
    {
        return response()->file(FileThumbnail::genericImagePath('not_found'))->setStatusCode(404);
    }
}
