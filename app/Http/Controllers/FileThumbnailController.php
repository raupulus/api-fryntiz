<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FileThumbnail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function auth;
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
            return response()->file(FileThumbnail::genericImagePath('not_found'));
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
            return response()->file(FileThumbnail::genericImagePath('not_found'));
        }

        if ($file->is_private && (int) $file->user_id !== (int) auth()->id()) {
            return response()->file(FileThumbnail::genericImagePath('not_authorized'));
        }

        return response()->file($thumbnail->storagePathFile);
    }
}
