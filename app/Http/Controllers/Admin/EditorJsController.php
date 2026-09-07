<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function filter_var;
use function in_array;
use function mb_convert_encoding;
use function mb_detect_encoding;
use function mb_substr;
use function parse_url;
use function preg_match;
use function trim;

/**
 * Lo que Editor.js necesita del servidor: subir ficheros y leer metadatos.
 *
 * Las herramientas `image`, `attaches` y `linkTool` no funcionan sin un
 * endpoint detrás, y por eso se quedaron fuera al migrar el editor a Filament:
 * en v2 sólo quedó `SimpleImage`, que guarda la imagen **incrustada en el JSON
 * como base64**. Eso hincha la fila de `content_page_raw` hasta reventarla y no
 * deja ninguna imagen en el módulo de ficheros.
 *
 * Editor.js exige su propio formato de respuesta —`{success: 1, file: {...}}` y
 * `{success: 1, meta: {...}}`—, así que estos métodos **no** usan el
 * `{success, message, data}` de la API v2: son endpoints del panel, no de la
 * API pública.
 */
class EditorJsController extends Controller
{
    /**
     * Sube un fichero desde el editor y devuelve su URL.
     *
     * Va al módulo `content-pages`, con lo cual queda como una fila de `files`
     * más: se ve en el panel, se sirve por `route('file.get', …)` y se le
     * generan miniaturas.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            // El campo se llama `file` porque es lo que se le configura a
            // `ImageTool`, y `AttachesTool` manda el suyo por el mismo sitio.
            'file' => ['required', 'file', 'max:8192'],
        ]);

        $subido = $request->file('file');
        $esImagen = str_starts_with((string) $subido->getMimeType(), 'image/');

        // `validate: false` porque por aquí entran también los adjuntos, que
        // no tienen por qué ser imágenes.
        $file = File::addFile($subido, 'content-pages', false, validate: false);

        if (! $file) {
            return response()->json(['success' => 0], 422);
        }

        return response()->json([
            'success' => 1,
            'file' => [
                // Editor.js pinta `file.url`. Para una imagen, la mediana; para
                // un adjunto, el fichero tal cual.
                'url' => $esImagen ? $file->thumbnail('large') : $file->url,
                'name' => $file->original_name ?? $file->name,
                'size' => $file->size,
                'extension' => $file->fileType?->extension,
                // Lo que necesita quien quiera volver a este fichero desde el
                // panel sin buscarlo por el nombre.
                'file_id' => $file->id,
            ],
        ]);
    }

    /**
     * Metadatos de una página externa, para la tarjeta de `linkTool`.
     *
     * ⚠️ Esto hace una petición saliente a una URL que elige quien escribe, o
     * sea **SSRF de manual** si se deja abierto: `http://169.254.169.254/` es
     * el servicio de metadatos de media nube, y `http://127.0.0.1:9200` es el
     * Elasticsearch de al lado. Por eso:
     *
     *  - sólo `http` y `https`, nada de `file://`, `gopher://` ni `dict://`;
     *  - el destino no puede resolver a una IP privada, de bucle o de enlace
     *    local;
     *  - tiempo de espera corto y sin seguir redirecciones a ciegas;
     *  - y sólo se leen los primeros 128 KB, que es donde está el `<head>`.
     *
     * Ante la duda, se responde `success: 0` y `linkTool` enseña el enlace
     * pelado, que es un resultado perfectamente válido.
     */
    public function urlMetadata(Request $request): JsonResponse
    {
        $url = trim((string) $request->query('url'));
        $vacio = response()->json(['success' => 0, 'meta' => []]);

        if (! $this->urlAlcanzable($url)) {
            return $vacio;
        }

        try {
            $respuesta = Http::timeout(5)
                ->connectTimeout(3)
                ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                ->withUserAgent('ApiRaupulusBot/1.0 (+https://api.raupulus.dev)')
                // Sin redirecciones: una redirección es otra URL que no ha
                // pasado por la comprobación de arriba.
                ->withoutRedirecting()
                ->get($url);
        } catch (\Throwable) {
            return $vacio;
        }

        if (! $respuesta->successful()) {
            return $vacio;
        }

        $html = mb_substr($respuesta->body(), 0, 131_072);
        $codificacion = mb_detect_encoding($html) ?: 'UTF-8';
        $html = mb_convert_encoding($html, 'UTF-8', $codificacion);

        return response()->json([
            'success' => 1,
            'meta' => [
                'title' => $this->extraerTitulo($html),
                'description' => $this->extraerMeta($html, 'description'),
                'image' => ['url' => $this->extraerMeta($html, 'og:image')],
            ],
        ]);
    }

    /**
     * ¿La URL es una página pública que se puede ir a buscar?
     */
    private function urlAlcanzable(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $partes = parse_url($url);

        if (! in_array($partes['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        $host = $partes['host'] ?? '';

        if ($host === '') {
            return false;
        }

        // Si el host ya es una IP, se comprueba directamente. Si es un nombre,
        // se resuelve: `interna.midominio.com` puede apuntar a 10.0.0.5.
        $ips = $this->resolver($host);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            $publica = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($publica === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Las IPs a las que apunta un host.
     *
     * Aparte para poder sustituirlo en los tests: si no, el caso feliz
     * dependería de que haya DNS y de que el dominio de prueba exista.
     *
     * @return list<string>
     */
    protected function resolver(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return gethostbynamel($host) ?: [];
    }

    private function extraerTitulo(string $html): string
    {
        return preg_match('!<title[^>]*>(.*?)</title>!is', $html, $m) === 1
            ? trim(html_entity_decode($m[1]))
            : '';
    }

    /**
     * Una `<meta>` por su nombre, admitiendo `name` y `property` en cualquier
     * orden respecto a `content`, que es como se escriben en la vida real.
     */
    private function extraerMeta(string $html, string $nombre): string
    {
        $escapado = preg_quote($nombre, '!');

        $patrones = [
            '!<meta[^>]+(?:name|property)=["\']'.$escapado.'["\'][^>]*content=["\'](.*?)["\']!is',
            '!<meta[^>]+content=["\'](.*?)["\'][^>]*(?:name|property)=["\']'.$escapado.'["\']!is',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $html, $m) === 1) {
                return trim(html_entity_decode($m[1]));
            }
        }

        return '';
    }
}
