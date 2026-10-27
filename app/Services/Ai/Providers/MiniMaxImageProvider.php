<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Models\AiProvider;
use App\Services\Ai\AiImageException;
use App\Services\Ai\GeneratedImage;

/**
 * MiniMax: `POST /image_generation` (modelo image-01).
 *
 * Devuelve las imágenes de forma síncrona en base64 en `data.image_base64`.
 * Para mejorar una imagen previa (`edit`), manda `subject_reference` con la
 * imagen original codificada en un Data URI.
 */
class MiniMaxImageProvider extends HttpImageProvider
{
    protected function request(AiProvider $provider, string $model, string $prompt, ?GeneratedImage $source, int $count, ?string $quality): array
    {
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'aspect_ratio' => '16:9',
            'response_format' => 'base64',
            'n' => $count,
        ];

        if ($source !== null) {
            $payload['subject_reference'] = [
                [
                    'type' => 'character',
                    'image_file' => $source->dataUri(),
                ],
            ];
        }

        return [$provider->provider->baseUrl().'/image_generation', $payload];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['Authorization' => 'Bearer '.$apiKey];
    }

    protected function images(AiProvider $provider, array $data): array
    {
        $baseResp = $data['base_resp'] ?? null;
        if (is_array($baseResp) && isset($baseResp['status_code']) && (int) $baseResp['status_code'] !== 0) {
            $code = (int) $baseResp['status_code'];
            $msg = (string) ($baseResp['status_msg'] ?? '');

            if ($code === 1008 || $this->isAboutCredit($msg)) {
                throw new AiImageException("La cuenta de MiniMax no tiene saldo o ha llegado a su límite de gasto. Recárgalo en {$provider->provider->billingPlace()} y vuelve a intentarlo.");
            }

            if (in_array($code, [1026, 1027], true) || $this->isContentFilter($msg, (string) $code)) {
                throw new AiImageException($this->filteredMessage($provider));
            }

            throw new AiImageException("MiniMax ha rechazado la petición ({$code}): {$msg}");
        }

        $items = $data['data']['image_base64'] ?? $data['data'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }

        $images = [];
        foreach ($items as $item) {
            $base64 = match (true) {
                is_string($item) => $item,
                is_array($item) && is_string($item['b64_json'] ?? null) => $item['b64_json'],
                is_array($item) && is_string($item['image_base64'] ?? null) => $item['image_base64'],
                default => null,
            };

            if ($base64 !== null) {
                $image = GeneratedImage::fromBase64($base64);
                if ($image !== null) {
                    $images[] = $image;
                }
            }
        }

        if ($images === []) {
            throw new AiImageException('MiniMax ha respondido, pero sin ninguna imagen. Vuelve a intentarlo.');
        }

        return $images;
    }
}
