<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\GeneratedImage;

/**
 * Grok (xAI): `POST /images/generations` y `POST /images/edits`, los dos sólo
 * en JSON (el SDK de OpenAI manda multipart a `edits` y xAI lo rechaza), con
 * `n` y en base64 (`response_format: b64_json`; por defecto da una URL
 * temporal). La imagen que se mejora va como data URI en `image.url`.
 *
 * **Sin probar con la API real**: la cuenta no tenía saldo (2026-10-17).
 */
class XaiImageProvider extends HttpImageProvider
{
    protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array
    {
        $payload = array_filter([
            'model' => $model,
            'prompt' => $prompt,
            'n' => $count,
            'response_format' => 'b64_json',
            'aspect_ratio' => '16:9',
            'resolution' => $quality ?? $provider->provider->defaultImageQuality(),
        ], fn (mixed $value): bool => $value !== null);

        if ($source !== null) {
            $payload['image'] = ['url' => $source->dataUri()];
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
