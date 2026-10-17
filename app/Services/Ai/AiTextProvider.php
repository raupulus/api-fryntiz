<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use Closure;

/**
 * Un conector con la API de texto de un proveedor de IA.
 *
 * El asistente no sabe de ningún proveedor: le pasa al conector la
 * instrucción de sistema y el prompt, y recibe el texto en trozos.
 */
interface AiTextProvider
{
    /**
     * Pide el texto en streaming y lo va pasando a `$callback($tipo, $texto)`:
     * `thought` (razonamiento, si el proveedor lo manda), `content` (el texto),
     * `warning` (la respuesta se ha cortado) y `error` (no hay respuesta; después
     * no llega nada más).
     *
     * Al navegador sólo le llega un mensaje para quien redacta; el detalle va
     * al log, sin la clave.
     *
     * @param  Closure(string $type, string $text): void  $callback
     */
    public function stream(
        AiProvider $provider,
        string $systemInstruction,
        string $prompt,
        ?AiReasoningLevelEnum $level,
        Closure $callback,
    ): void;

    /**
     * Una llamada real mínima para ver que la clave y el modelo responden.
     *
     * Se genera texto en vez de listar modelos: Gemini da 200 en
     * `GET models/…` aunque el modelo esté apagado.
     *
     * @return array{ok: bool, message: string}
     */
    public function probe(AiProvider $provider): array;
}
