<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileThumbnail;
use Illuminate\Http\Request;
use function auth;
use function response;

/**
 * Class FileThumbnailController
 * @package App\Http\Controllers
 */
class FileThumbnailController extends Controller
{
    /**
     * Devuelve un archivo del sistema de archivos.
     *
     * @param string $module Grupo del archivo.
     * @param int $id Identificador del archivo.
     * @param string|null $slug Slug del archivo.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function get(string $module, int $id, string|null $slug = null)
    {
        $file = FileThumbnail::find($id);

        if (!$file) {
            return response()->file(FileThumbnail::$genericImages['not_found']);
        }

        ## Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            return response()->file(FileThumbnail::$genericImages['not_authorized']);
        }

        return response()->file($file->storagePathFile);
    }
}
