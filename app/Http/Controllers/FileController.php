<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
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
     * @param string $module Grupo del archivo.
     * @param int $id Identificador del archivo.
     * @param string|null $slug Slug del archivo.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function get(string $module, int $id, string|null $slug = null)
    {
        $file = File::find($id);

        if (!$file) {
            return response()->file(File::$genericImages['not_found']);
        }

        ## Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            return response()->file(File::$genericImages['not_authorized']);
        }

        return response()->file($file->storagePathFile);
    }

    /**
     * Redimensiona una imagen y la devuelve a ese tamaño.
     *
     * @param string $module Grupo del archivo.
     * @param int $id Identificador del archivo.
     * @param string $slug Slug del archivo.
     * @param int $width Ancho del archivo a redimensionar.
     */
    public function resizeAndGet(string $module, int $id, string $slug, int $width)
    {

        $file = File::find($id);

        if (!$file) {
            // TODO → Resize this file.
            return response()->file(File::$genericImages['not_found']);
        }


        // TODO → Check if file is an image.

        if ($file->type !== 'image') {
            // TODO → Resize this file.
            return response()->file(File::$genericImages['not_image']);
        }



        ## Compruebo si es un archivo privado.
        if ($file->is_private && ($file->user_id !== auth()->id())) {
            // TODO → Resize this file.
            return response()->file(File::$genericImages['not_authorized']);
        }



        $image = Image::make($file->storagePathFile);
        $image->resize($width);


        $image->resize(150, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        //$image->save('tmp??');


        // TODO → Cachear la imagen o comprobar si ya existe ahí.


        return $image->response();
        //return response()->file($file->storagePathFile)->deleteFileAfterSend();

    }

    /**
     * Procesa la subida de un archivo.
     */
    public function upload(Request $request)
    {

    }

    /**
     * Devuelve la descarga de un archivo.
     *
     * @param string $module Grupo del archivo.
     * @param int $id Identificador del archivo.
     * @param string|null $slug Slug del archivo.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $module, int $id, string|null $slug = null)
    {

    }

    /**
     * Elimina un archivo.
     *
     * @param int $id
     *
     * @return void
     */
    public function delete( int $id)
    {
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
