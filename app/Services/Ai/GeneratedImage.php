<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Una imagen de la IA (o la que se le manda para mejorarla): los bytes, y el
 * tipo y el tamaño leídos de ellos, no de lo que diga la API.
 */
final class GeneratedImage
{
    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private function __construct(
        public readonly string $bytes,
        public readonly string $mime,
        public readonly int $width,
        public readonly int $height,
    ) {}

    /**
     * `null` si no es una imagen JPEG, PNG o WebP.
     */
    public static function fromBytes(string $bytes): ?self
    {
        $info = $bytes === '' ? false : @getimagesizefromstring($bytes);

        if ($info === false || ! isset(self::MIMES[$info['mime']])) {
            return null;
        }

        return new self($bytes, $info['mime'], (int) $info[0], (int) $info[1]);
    }

    public static function fromBase64(string $data): ?self
    {
        $bytes = base64_decode($data, true);

        return $bytes === false ? null : self::fromBytes($bytes);
    }

    public function extension(): string
    {
        return self::MIMES[$this->mime];
    }

    public function base64(): string
    {
        return base64_encode($this->bytes);
    }

    public function dataUri(): string
    {
        return 'data:'.$this->mime.';base64,'.$this->base64();
    }
}
