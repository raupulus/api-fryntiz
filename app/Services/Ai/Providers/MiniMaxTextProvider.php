<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use Closure;

/**
 * MiniMax: Chat Completions (`POST /chat/completions`) en streaming compatible con OpenAI.
 *
 * El razonamiento de modelos como MiniMax-M3.1-Flash llega en `choices[0].delta.reasoning_content`
 * y la respuesta en `choices[0].delta.content`. El stream termina con `data: [DONE]`.
 */
class MiniMaxTextProvider extends HttpTextProvider
{
    protected function request(AiProvider $provider, string $systemInstruction, string $prompt, ?AiReasoningLevelEnum $level): array
    {
        $payload = [
            'model' => $provider->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $prompt],
            ],
            'stream' => true,
        ];

        if ($provider->sends_reasoning_level && $level !== null) {
            $payload['reasoning_effort'] = $level->value;
        }

        return [$provider->provider->baseUrl().'/chat/completions', $payload];
    }

    protected function authHeaders(string $apiKey): array
    {
        return ['Authorization' => 'Bearer '.$apiKey];
    }

    protected function handleEvent(AiProvider $provider, array $data, Closure $callback): bool
    {
        if (($data['object'] ?? null) === 'error' || isset($data['error'])) {
            $message = (string) ($data['message'] ?? $data['error']['message'] ?? '');
            $callback('error', 'MiniMax ha cortado la respuesta con un error'.($message !== '' ? ": {$message}" : '.'));

            return false;
        }

        $baseResp = $data['base_resp'] ?? null;
        if (is_array($baseResp) && isset($baseResp['status_code']) && (int) $baseResp['status_code'] !== 0) {
            $message = (string) ($baseResp['status_msg'] ?? '');
            $callback('error', 'MiniMax ha devuelto un error'.($message !== '' ? ": {$message}" : '.'));

            return false;
        }

        $choice = $data['choices'][0] ?? null;

        if (! is_array($choice)) {
            return true;
        }

        $delta = $choice['delta'] ?? null;

        if (is_array($delta)) {
            $thought = $delta['reasoning_content'] ?? null;
            if (is_string($thought) && $thought !== '') {
                $callback('thought', $thought);
            }

            $content = $delta['content'] ?? null;
            if (is_string($content) && $content !== '') {
                $callback('content', $content);
            }
        }

        return match ((string) ($choice['finish_reason'] ?? '')) {
            'length' => $this->warn($callback, 'MiniMax no ha terminado: la respuesta se ha cortado por larga. Revisa el final antes de aplicarlo.'),
            'content_filter' => $this->fail($callback, 'MiniMax ha cortado la respuesta por filtros de contenido.'),
            'error' => $this->fail($callback, 'MiniMax no ha podido terminar la respuesta.'),
            default => true,
        };
    }

    /**
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function warn(Closure $callback, string $message): bool
    {
        $callback('warning', $message);

        return true;
    }

    /**
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function fail(Closure $callback, string $message): bool
    {
        $callback('error', $message);

        return false;
    }
}
