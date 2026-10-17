<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\AiImageException;
use App\Services\Ai\GeneratedImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mistral no tiene API de imágenes: un modelo de texto usa su herramienta
 * `image_generation` en `POST /conversations` (en beta). Una imagen por
 * petición, y no sabe mejorar una que se le mande.
 *
 * Probado con la API real el 2026-10-17 (`mistral-medium-3-5`, unos 8 s): la
 * imagen llega como una entrada `tool.execution` cuyo `info.result` es un JSON
 * con la URL firmada (y temporal) de un blob de Azure, en JPEG de 1024 × 768;
 * no hace caso del 16:9. Según su documentación también puede llegar como un
 * trozo `tool_file` con un `file_id`, que se descarga de `/files/{id}/content`.
 *
 * La URL viene en la respuesta de la API: sólo se descarga por HTTPS y de los
 * dominios de Mistral y de su almacenamiento, para que una respuesta rara no
 * haga que el servidor pida cualquier dirección.
 */
class MistralImageProvider extends HttpImageProvider
{
    private const DOWNLOAD_HOSTS = ['.blob.core.windows.net', '.mistral.ai'];

    private const MAX_BYTES = 20 * 1024 * 1024;

    protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array
    {
        return [
            $provider->provider->baseUrl().'/conversations',
            [
                'model' => $model,
                'inputs' => "Genera una imagen con tu herramienta de imágenes. No hace falta que escribas nada más.\n\n".$prompt,
                'tools' => [['type' => 'image_generation']],
                'store' => false,
            ],
        ];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['Authorization' => 'Bearer '.$apiKey];
    }

    protected function images(AiProvider $provider, array $data): array
    {
        $said = '';

        foreach (is_array($data['outputs'] ?? null) ? $data['outputs'] : [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['type'] ?? null) === 'tool.execution' && is_string($entry['info']['result'] ?? null)) {
                $result = json_decode($entry['info']['result'], true);

                if (is_array($result) && is_string($result['url'] ?? null)) {
                    return [$this->download($result['url'])];
                }
            }

            foreach (is_array($entry['content'] ?? null) ? $entry['content'] : [] as $chunk) {
                if (is_array($chunk) && ($chunk['type'] ?? null) === 'tool_file' && is_string($chunk['file_id'] ?? null)) {
                    return [$this->file($provider, $chunk['file_id'])];
                }
            }

            if (($entry['type'] ?? null) === 'message.output' && is_string($entry['content'] ?? null)) {
                $said = $entry['content'];
            }
        }

        throw new AiImageException('Mistral no ha generado ninguna imagen'.($said !== '' ? ': «'.Str::limit(trim($said), 200).'»' : '.').' Vuelve a intentarlo o cambia la descripción.');
    }

    private function download(string $url): GeneratedImage
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (($parts['scheme'] ?? null) !== 'https' || ! Str::endsWith($host, self::DOWNLOAD_HOSTS)) {
            Log::warning('Mistral: la imagen llega de un sitio que no se descarga', ['host' => $host]);

            throw new AiImageException('Mistral ha dejado la imagen en un sitio desde el que no se descarga. Vuelve a intentarlo.');
        }

        try {
            $response = Http::timeout(60)->get($url);
        } catch (Throwable $e) {
            Log::error('Mistral: no se ha podido descargar la imagen: '.$e->getMessage());

            throw new AiImageException('No se ha podido descargar la imagen de Mistral. Vuelve a intentarlo.');
        }

        return $this->imageFrom($response->successful() ? $response->body() : '');
    }

    private function file(AiProvider $provider, string $fileId): GeneratedImage
    {
        try {
            $response = Http::withHeaders($this->authHeaders((string) $provider->api_key))->timeout(60)
                ->get($provider->provider->baseUrl().'/files/'.rawurlencode($fileId).'/content');
        } catch (Throwable $e) {
            Log::error('Mistral: no se ha podido descargar la imagen: '.$e->getMessage());

            throw new AiImageException('No se ha podido descargar la imagen de Mistral. Vuelve a intentarlo.');
        }

        return $this->imageFrom($response->successful() ? $response->body() : '');
    }

    private function imageFrom(string $bytes): GeneratedImage
    {
        $image = strlen($bytes) <= self::MAX_BYTES ? GeneratedImage::fromBytes($bytes) : null;

        if ($image === null) {
            throw new AiImageException('Mistral ha dado una imagen que no se puede leer. Vuelve a intentarlo.');
        }

        return $image;
    }
}
