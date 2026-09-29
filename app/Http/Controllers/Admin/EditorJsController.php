<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ContentUploadException;
use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Services\Content\ContentFileService;
use App\Services\Http\PublicUrlFetcher;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo que Editor.js necesita del servidor para un contenido: subir ficheros,
 * descargar una imagen pegada por URL y leer los metadatos de un enlace.
 *
 * Las rutas cuelgan del contenido (`/admin/contents/{content}/editor/…`) y
 * piden la política `update` sobre él (B5 de la auditoría de contenidos): antes
 * eran genéricas y cualquiera con acceso al panel subía ficheros sin saber a
 * qué contenido iban. Cada fichero queda vinculado a su contenido.
 *
 * Editor.js exige su propio formato de respuesta —`{success: 1, file: {...}}` y
 * `{success: 1, meta: {...}}`—, así que estos métodos **no** usan el
 * `{success, message, data}` de la API v2: son endpoints del panel. Un error
 * es `{success: 0, message}` con el motivo, y el editor lo enseña.
 */
class EditorJsController extends Controller
{
    public function __construct(
        private readonly ContentFileService $files,
        private readonly PublicUrlFetcher $fetcher,
    ) {}

    /**
     * Sube un fichero (imagen o adjunto) desde el editor.
     */
    public function upload(Request $request, Content $content): JsonResponse
    {
        $request->validate(['file' => ['required', 'file']], [
            'file.required' => 'No ha llegado ningún fichero.',
            'file.file' => 'No ha llegado el fichero: puede que pese más de lo que admite el servidor.',
        ]);

        return $this->respond(fn (): array => $this->files->store($content, $request->file('file')));
    }

    /**
     * Descarga una imagen pegada por URL (C5), con el mismo filtro de
     * direcciones internas que los metadatos.
     */
    public function uploadByUrl(Request $request, Content $content): JsonResponse
    {
        $url = trim((string) $request->input('url'));

        return $this->respond(fn (): array => $this->files->storeFromUrl($content, $url));
    }

    /**
     * Metadatos de una página externa, para la tarjeta de `linkTool`.
     *
     * Sólo se leen los primeros 128 KB, que es donde está el `<head>`. Ante la
     * duda se responde `success: 0` y `linkTool` enseña el enlace pelado, que es
     * un resultado perfectamente válido.
     */
    public function urlMetadata(Request $request, Content $content): JsonResponse
    {
        $url = trim((string) $request->query('url'));
        $empty = response()->json(['success' => 0, 'meta' => []]);

        $response = $this->fetcher->fetch($url, 131_072, ['Accept' => 'text/html,application/xhtml+xml'], timeout: 5);

        if ($response === null || ! $response->successful()) {
            return $empty;
        }

        $html = $response->body;
        $encoding = mb_detect_encoding($html) ?: 'UTF-8';
        $html = mb_convert_encoding($html, 'UTF-8', $encoding);

        return response()->json([
            'success' => 1,
            'meta' => [
                'title' => $this->extractTitle($html) ?: $this->extractMeta($html, 'og:title'),
                // Muchas webs sólo la ponen para las redes sociales.
                'description' => $this->extractMeta($html, 'description')
                    ?: $this->extractMeta($html, 'og:description')
                    ?: $this->extractMeta($html, 'twitter:description'),
                'image' => ['url' => $this->extractMeta($html, 'og:image') ?: $this->extractMeta($html, 'twitter:image')],
            ],
        ]);
    }

    /**
     * @param  Closure(): array<string, mixed>  $store
     */
    private function respond(Closure $store): JsonResponse
    {
        try {
            return response()->json(['success' => 1, 'file' => $store()]);
        } catch (ContentUploadException $e) {
            return response()->json(['success' => 0, 'message' => $e->getMessage()], 422);
        }
    }

    private function extractTitle(string $html): string
    {
        return preg_match('!<title[^>]*>(.*?)</title>!is', $html, $m) === 1
            ? trim(html_entity_decode($m[1]))
            : '';
    }

    /**
     * Una `<meta>` por su nombre, admitiendo `name` y `property` en cualquier
     * orden respecto a `content`, que es como se escriben en la vida real.
     */
    private function extractMeta(string $html, string $name): string
    {
        $escaped = preg_quote($name, '!');

        $patterns = [
            '!<meta[^>]+(?:name|property)=["\']'.$escaped.'["\'][^>]*content=["\'](.*?)["\']!is',
            '!<meta[^>]+content=["\'](.*?)["\'][^>]*(?:name|property)=["\']'.$escaped.'["\']!is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m) === 1) {
                return trim(html_entity_decode($m[1]));
            }
        }

        return '';
    }
}
