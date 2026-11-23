<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sirve la documentación estática de la API generada por Scribe.
 */
class ScribeDocsController extends Controller
{
    public function index(): View
    {
        return view('scribe.index');
    }

    public function openapi(): BinaryFileResponse
    {
        $path = storage_path('app/scribe/openapi.yaml');
        abort_unless(is_file($path), 404, 'La especificación OpenAPI no está generada.');

        return response()->file($path, ['Content-Type' => 'application/yaml']);
    }

    public function postman(): BinaryFileResponse
    {
        $path = storage_path('app/scribe/collection.json');
        abort_unless(is_file($path), 404, 'La colección de Postman no está generada.');

        return response()->file($path, ['Content-Type' => 'application/json']);
    }
}
