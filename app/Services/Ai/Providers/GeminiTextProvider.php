<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use Closure;

/**
 * Google Gemini: `streamGenerateContent` con `alt=sse`.
 *
 * La clave va en la cabecera `x-goog-api-key`. No se manda `temperature`: los
 * modelos 3.x lo ignoran y Google pide quitarlo. El razonamiento va en
 * `thinkingConfig`: `thinkingLevel` (si el proveedor lo tiene marcado) e
 * `includeThoughts`, que es lo que hace que lleguen los resúmenes; sin él, el
 * modal se quedaba mudo mientras Gemini piensa. Google los escribe en inglés
 * aunque se le pidan en español.
 */
class GeminiTextProvider extends HttpTextProvider
{
    /**
     * Motivos de fin que no son «terminado»: la respuesta se ha cortado.
     */
    private const FINISH_REASONS = [
        'MAX_TOKENS' => 'la respuesta se ha cortado por larga',
        'SAFETY' => 'los filtros de seguridad de Google la han cortado',
        'RECITATION' => 'Google la ha cortado por parecerse demasiado a un texto publicado',
        'BLOCKLIST' => 'Google la ha cortado por contener términos bloqueados',
        'PROHIBITED_CONTENT' => 'Google la ha cortado por contenido prohibido',
        'SPII' => 'Google la ha cortado por contener datos personales sensibles',
    ];

    protected function request(AiProvider $provider, string $systemInstruction, string $prompt, ?AiReasoningLevelEnum $level): array
    {
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
        ];

        $thinking = array_filter([
            'thinkingLevel' => $provider->sends_reasoning_level ? $level?->value : null,
            'includeThoughts' => $provider->requests_reasoning_summary ? true : null,
        ], fn ($value): bool => $value !== null);

        if ($thinking !== []) {
            $payload['generationConfig'] = ['thinkingConfig' => $thinking];
        }

        return [$provider->provider->baseUrl()."/models/{$provider->model}:streamGenerateContent?alt=sse", $payload];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['x-goog-api-key' => $apiKey];
    }

    protected function handleEvent(AiProvider $provider, array $data, Closure $callback): bool
    {
        // La petición entera bloqueada: no llega ningún candidato.
        if (filled($data['promptFeedback']['blockReason'] ?? null)) {
            $callback('error', 'Gemini ha bloqueado la petición ('.$data['promptFeedback']['blockReason'].'). Cambia la instrucción o el texto y vuelve a intentarlo.');

            return false;
        }

        $candidate = $data['candidates'][0] ?? null;

        if (! is_array($candidate)) {
            return true;
        }

        foreach ($candidate['content']['parts'] ?? [] as $part) {
            $text = is_array($part) ? (string) ($part['text'] ?? '') : '';

            if ($text === '') {
                continue;
            }

            $callback(($part['thought'] ?? false) === true ? 'thought' : 'content', $text);
        }

        $reason = (string) ($candidate['finishReason'] ?? '');

        if (isset(self::FINISH_REASONS[$reason])) {
            $callback('warning', 'Gemini no ha terminado: '.self::FINISH_REASONS[$reason].'. Revisa el final antes de aplicarlo.');
        } elseif (! in_array($reason, ['', 'STOP', 'FINISH_REASON_UNSPECIFIED'], true)) {
            $callback('warning', 'Gemini no ha terminado ('.$reason.'). Revisa el resultado antes de aplicarlo.');
        }

        return true;
    }
}
