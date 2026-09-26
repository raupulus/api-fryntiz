<?php

declare(strict_types=1);

namespace App\Services\Http;

/**
 * Respuesta de `PublicUrlFetcher`: el cuerpo hasta el tope pedido, y si ha
 * cabido entero.
 */
final readonly class PublicUrlResponse
{
    public function __construct(
        public int $status,
        public string $body,
        public string $contentType,
        public bool $complete,
    ) {}

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
