<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\AiImageException;
use App\Services\Ai\AiImageProvider;
use App\Services\Ai\GeneratedImage;
use App\Services\Ai\Providers\Concerns\DescribesApiErrors;
use Closure;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lo común de los conectores de imágenes: las peticiones (varias a la vez si
 * el proveedor sólo da una por petición), los errores y la prueba.
 *
 * Son respuestas JSON normales, sin streaming, y una imagen puede tardar hasta
 * un par de minutos (OpenAI): mientras, Guzzle llama a `progress` cada poco y
 * eso llama a `$tick`. La clave va en una cabecera, nunca en la URL.
 */
abstract class HttpImageProvider implements AiImageProvider
{
    use DescribesApiErrors;

    protected const TIMEOUT = 180;

    /**
     * La petición de `$count` imágenes: la URL y el cuerpo.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    abstract protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array;

    /**
     * Las cabeceras de autenticación con la clave.
     *
     * @return array<string, string>
     */
    abstract protected function authHeaders(string $apiKey): array;

    /**
     * Las imágenes de una respuesta correcta. Lanza `AiImageException` si no
     * trae ninguna (filtros, negativa del modelo…).
     *
     * @param  array<string, mixed>  $data
     * @return list<GeneratedImage>
     */
    abstract protected function images(AiProvider $provider, array $data): array;

    public function generate(AiProvider $provider, string $prompt, int $count, ?string $quality = null, ?Closure $tick = null): array
    {
        return $this->run($provider, $prompt, null, $count, $quality, $tick);
    }

    public function edit(AiProvider $provider, GeneratedImage $source, string $prompt, int $count, ?string $quality = null, ?Closure $tick = null): array
    {
        if (! $provider->provider->supportsImageEditing()) {
            throw new AiImageException($provider->provider->label().' no sabe mejorar una imagen: sólo las genera. Elige otro proveedor.');
        }

        return $this->run($provider, $prompt, $source, $count, $quality, $tick);
    }

    public function probe(AiProvider $provider): array
    {
        $qualities = array_keys($provider->provider->imageQualities());

        try {
            $images = $this->generate($provider, 'Imagen de prueba: un círculo azul sobre fondo blanco, sin texto.', 1, $qualities[0] ?? null);
        } catch (AiImageException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'message' => sprintf('Ha generado una imagen de %d × %d.', $images[0]->width, $images[0]->height)];
    }

    /**
     * @param  (Closure(): void)|null  $tick
     * @return list<GeneratedImage>
     */
    private function run(AiProvider $provider, string $prompt, ?GeneratedImage $source, int $count, ?string $quality, ?Closure $tick): array
    {
        $label = $provider->provider->label();
        $apiKey = $provider->api_key;
        $model = trim((string) $provider->image_model);

        if (blank($apiKey)) {
            throw new AiImageException("La clave de {$label} no está puesta o no se puede leer. Vuelve a pegarla en Configuración → Proveedores de IA.");
        }

        if ($model === '') {
            throw new AiImageException("{$label} no tiene modelo de imágenes: ponlo en Configuración → Proveedores de IA.");
        }

        // Gemini y Mistral dan una imagen por petición: para más, a la vez.
        $batches = [];
        for ($left = max(1, $count); $left > 0; $left -= $batches[array_key_last($batches)]) {
            $batches[] = min($left, $provider->provider->maxImagesPerRequest());
        }

        $options = $tick === null ? [] : ['progress' => fn () => $tick()];
        $headers = $this->authHeaders((string) $apiKey);

        $responses = Http::pool(fn (Pool $pool): array => array_map(function (int $batch) use ($pool, $provider, $model, $prompt, $source, $quality, $headers, $options) {
            [$url, $payload] = $this->request($provider, $model, $prompt, $source, $batch, $quality);

            return $pool->withHeaders($headers)->withOptions($options)
                ->timeout(self::TIMEOUT)->connectTimeout(15)
                ->acceptJson()->post($url, $payload);
        }, $batches));

        $images = [];
        $errors = [];

        foreach ($responses as $response) {
            try {
                array_push($images, ...$this->imagesOf($provider, $model, $response));
            } catch (AiImageException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($images === []) {
            throw new AiImageException($errors[0] ?? "{$label} no ha devuelto ninguna imagen. Vuelve a intentarlo.");
        }

        return array_slice($images, 0, $count);
    }

    /**
     * @return list<GeneratedImage>
     */
    private function imagesOf(AiProvider $provider, string $model, mixed $response): array
    {
        $label = $provider->provider->label();

        if (! $response instanceof Response) {
            Log::error("{$label}: fallo de conexión al pedir una imagen", ['exception' => $response instanceof Throwable ? $response->getMessage() : null]);

            throw new AiImageException("No se ha podido conectar con {$label}. Vuelve a intentarlo en un momento.");
        }

        if ($response->failed()) {
            [$message, $code] = $this->apiError($response);
            Log::warning("{$label}: error de la API de imágenes", ['status' => $response->status(), 'code' => $code, 'error' => $message]);

            throw new AiImageException($this->isContentFilter($message, $code)
                ? $this->filteredMessage($provider)
                : $this->errorMessage($provider, $response->status(), $message, $code, $model));
        }

        $data = $response->json();

        return $this->images($provider, is_array($data) ? $data : []);
    }

    /**
     * Si el error es que la petición no pasa los filtros de contenido.
     */
    protected function isContentFilter(string $message, ?string $code): bool
    {
        return in_array($code, ['moderation_blocked', 'content_policy_violation', 'content_filter'], true)
            || (bool) preg_match('/moderation|content policy|safety system/i', $message);
    }

    protected function filteredMessage(AiProvider $provider): string
    {
        return $provider->provider->label().' no ha querido hacer esta imagen: no pasa sus filtros de contenido. Cambia la descripción y vuelve a intentarlo.';
    }

    /**
     * Las imágenes en base64 de una lista (`data[].b64_json` en OpenAI y Grok).
     *
     * @param  array<mixed>  $items
     * @return list<GeneratedImage>
     */
    protected function base64Images(AiProvider $provider, array $items, string $key): array
    {
        $images = [];

        foreach ($items as $item) {
            $image = is_array($item) && is_string($item[$key] ?? null) ? GeneratedImage::fromBase64($item[$key]) : null;

            if ($image !== null) {
                $images[] = $image;
            }
        }

        if ($images === []) {
            throw new AiImageException($provider->provider->label().' ha respondido, pero sin ninguna imagen. Vuelve a intentarlo.');
        }

        return $images;
    }
}
