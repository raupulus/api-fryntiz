<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\GeneratedImage;

/**
 * OpenAI: `POST /images/generations` y, para mejorar una, `POST /images/edits`,
 * los dos en JSON y con `n`. La imagen llega en base64 (`data[].b64_json`):
 * los modelos GPT no devuelven URL ni admiten `response_format`.
 *
 * `1536x1024` lo admiten todos los modelos GPT de imagen; el recorte a 16:9 lo
 * hace quien la recibe. **Sin probar con la API real**: no había clave
 * (2026-10-17). Requiere la organización verificada en OpenAI.
 */
class OpenAiImageProvider extends HttpImageProvider
{
    protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array
    {
        $payload = array_filter([
            'model' => $model,
            'prompt' => $prompt,
            'n' => $count,
            'size' => '1536x1024',
            'quality' => $quality ?? $provider->provider->defaultImageQuality(),
            'output_format' => 'png',
        ], fn (mixed $value): bool => $value !== null);

        if ($source !== null) {
            $payload['images'] = [['image_url' => $source->dataUri()]];
        }

        return [$provider->provider->baseUrl().($source === null ? '/images/generations' : '/images/edits'), $payload];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['Authorization' => 'Bearer '.$apiKey];
    }

    protected function images(AiProvider $provider, array $data): array
    {
        return $this->base64Images($provider, is_array($data['data'] ?? null) ? $data['data'] : [], 'b64_json');
    }
}
