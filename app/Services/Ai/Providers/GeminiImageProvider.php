<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\AiImageException;
use App\Services\Ai\GeneratedImage;

/**
 * Gemini: `generateContent` con un modelo de imagen («Nano Banana»), pidiendo
 * sólo imagen. Una por petición: no tiene `n`. Para mejorar una, va en la
 * misma petición, delante del texto. Probado con la API real el 2026-10-17
 * (`gemini-nano-banana-2.1`: 16:9 a 1K sale de 1376 × 768, en JPEG, en unos
 * 14 s).
 *
 * Con razonamiento puede mandar imágenes de prueba antes de la buena: vale la
 * última que no sea un pensamiento.
 */
class GeminiImageProvider extends HttpImageProvider
{
    /**
     * Motivos de corte que son de los filtros, no un fallo.
     */
    private const FILTERED = ['SAFETY', 'IMAGE_SAFETY', 'PROHIBITED_CONTENT', 'IMAGE_PROHIBITED_CONTENT', 'BLOCKLIST', 'SPII', 'RECITATION', 'IMAGE_RECITATION'];

    protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array
    {
        $parts = $source === null ? [] : [['inlineData' => ['mimeType' => $source->mime, 'data' => $source->base64()]]];
        $parts[] = ['text' => $prompt];

        return [
            $provider->provider->baseUrl().'/models/'.rawurlencode($model).':generateContent',
            [
                'contents' => [['role' => 'user', 'parts' => $parts]],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE'],
                    'imageConfig' => ['aspectRatio' => '16:9', 'imageSize' => $quality ?? $provider->provider->defaultImageQuality()],
                ],
            ],
        ];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['x-goog-api-key' => $apiKey];
    }

    protected function images(AiProvider $provider, array $data): array
    {
        if (filled($data['promptFeedback']['blockReason'] ?? null)) {
            throw new AiImageException($this->filteredMessage($provider));
        }

        $candidate = $data['candidates'][0] ?? [];
        $parts = is_array($candidate['content']['parts'] ?? null) ? $candidate['content']['parts'] : [];

        foreach (array_reverse($parts) as $part) {
            if (is_array($part) && empty($part['thought']) && is_string($part['inlineData']['data'] ?? null)) {
                $image = GeneratedImage::fromBase64($part['inlineData']['data']);

                if ($image !== null) {
                    return [$image];
                }
            }
        }

        $reason = (string) ($candidate['finishReason'] ?? '');

        throw new AiImageException(in_array($reason, self::FILTERED, true)
            ? $this->filteredMessage($provider)
            : 'Gemini no ha devuelto ninguna imagen'.($reason !== '' && $reason !== 'STOP' ? " ({$reason})" : '').'. Vuelve a intentarlo o cambia la descripción.');
    }
}
