<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiProvider;
use Closure;

/**
 * Un conector con la API de imágenes de un proveedor de IA: generar a partir
 * de una descripción y mejorar una imagen que se le manda.
 *
 * Si no sale ninguna imagen, lanza `AiImageException` con un mensaje para
 * quien redacta; el detalle va al log, sin la clave.
 */
interface AiImageProvider
{
    /**
     * `$quality` es una de `AiProviderEnum::imageQualities()`, o `null` para
     * la de por defecto. `$tick` se llama mientras se espera, para que quien
     * pide pueda dar señales de vida.
     *
     * @param  (Closure(): void)|null  $tick
     * @return list<GeneratedImage>
     */
    public function generate(AiProvider $provider, string $prompt, int $count, ?string $quality = null, ?Closure $tick = null): array;

    /**
     * Lo mismo, a partir de `$source`.
     *
     * @param  (Closure(): void)|null  $tick
     * @return list<GeneratedImage>
     */
    public function edit(AiProvider $provider, GeneratedImage $source, string $prompt, int $count, ?string $quality = null, ?Closure $tick = null): array;

    /**
     * Genera una imagen de verdad (cuesta unos céntimos) para ver que la
     * clave y el modelo de imágenes responden.
     *
     * @return array{ok: bool, message: string}
     */
    public function probe(AiProvider $provider): array;
}
