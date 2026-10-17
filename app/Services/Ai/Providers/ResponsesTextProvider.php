<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use Closure;

/**
 * OpenAI y xAI (Grok): la API Responses (`POST /responses`) en streaming.
 *
 * xAI da Chat Completions por obsoleta (sólo la mantiene como «legacy») y con
 * ella no devuelve el razonamiento de los modelos 4.x; OpenAI recomienda
 * Responses para sus modelos de razonamiento. Las dos entienden la misma
 * petición y mandan los mismos eventos, cada uno con su `type`.
 *
 * `store: false`: la respuesta no se queda guardada en la cuenta del
 * proveedor. El razonamiento va en `reasoning`: `effort` si el proveedor
 * tiene marcado el nivel, y `summary: auto` si tiene marcado el resumen.
 */
class ResponsesTextProvider extends HttpTextProvider
{
    /**
     * Motivos de `response.incomplete`.
     */
    private const INCOMPLETE_REASONS = [
        'max_output_tokens' => 'la respuesta se ha cortado por larga',
        'content_filter' => 'los filtros de seguridad la han cortado',
    ];

    /**
     * Lo que se va recibiendo de una negativa del modelo, que llega por
     * trozos como el texto.
     */
    private string $refusal = '';

    protected function request(AiProvider $provider, string $systemInstruction, string $prompt, ?AiReasoningLevelEnum $level): array
    {
        $this->refusal = '';

        $payload = [
            'model' => $provider->model,
            'input' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $prompt],
            ],
            'stream' => true,
            'store' => false,
        ];

        $reasoning = array_filter([
            'effort' => $provider->sends_reasoning_level ? $level?->value : null,
            'summary' => $provider->requests_reasoning_summary ? 'auto' : null,
        ], fn ($value): bool => $value !== null);

        if ($reasoning !== []) {
            $payload['reasoning'] = $reasoning;
        }

        return [$provider->provider->baseUrl().'/responses', $payload];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['Authorization' => 'Bearer '.$apiKey];
    }

    protected function handleEvent(AiProvider $provider, array $data, Closure $callback): bool
    {
        $label = $provider->provider->label();
        $delta = is_string($data['delta'] ?? null) ? $data['delta'] : '';

        switch ((string) ($data['type'] ?? '')) {
            case 'response.output_text.delta':
                if ($delta !== '') {
                    $callback('content', $delta);
                }

                return true;

            case 'response.reasoning_summary_text.delta':
            case 'response.reasoning_text.delta':
                if ($delta !== '') {
                    $callback('thought', $delta);
                }

                return true;

                // Cada parte del resumen del razonamiento es un párrafo.
            case 'response.reasoning_summary_part.added':
                if ((int) ($data['summary_index'] ?? 0) > 0) {
                    $callback('thought', "\n\n");
                }

                return true;

            case 'response.refusal.delta':
                $this->refusal .= $delta;

                return true;

            case 'response.refusal.done':
                $refusal = trim(is_string($data['refusal'] ?? null) ? $data['refusal'] : $this->refusal);
                $callback('error', "{$label} se ha negado a hacerlo".($refusal !== '' ? ": {$refusal}" : '.'));

                return false;

            case 'response.incomplete':
                $reason = (string) ($data['response']['incomplete_details']['reason'] ?? '');
                $callback('warning', isset(self::INCOMPLETE_REASONS[$reason])
                    ? "{$label} no ha terminado: ".self::INCOMPLETE_REASONS[$reason].'. Revisa el final antes de aplicarlo.'
                    : "{$label} no ha terminado".($reason !== '' ? " ({$reason})" : '').'. Revisa el resultado antes de aplicarlo.');

                return true;

            case 'response.failed':
                $message = (string) ($data['response']['error']['message'] ?? '');
                $callback('error', "{$label} no ha podido terminar la respuesta".($message !== '' ? ": {$message}" : '.'));

                return false;

            case 'error':
                $message = (string) ($data['message'] ?? $data['error']['message'] ?? '');
                $callback('error', "{$label} ha cortado la respuesta con un error".($message !== '' ? ": {$message}" : '.'));

                return false;

            default:
                return true;
        }
    }
}
