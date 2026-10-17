<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use Closure;

/**
 * Mistral: Chat Completions (`POST /chat/completions`) en streaming.
 *
 * Con `reasoning_effort: high`, el razonamiento llega dentro de `delta.content`,
 * que deja de ser un texto y pasa a ser una lista de trozos: `thinking` (con
 * su propia lista de textos) y `text`. Sin razonamiento, `delta.content` es
 * un texto. El stream termina con `data: [DONE]`.
 */
class MistralTextProvider extends HttpTextProvider
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

        // Razona o no: `high` es el único nivel que admiten todos sus modelos
        // con razonamiento (`AiProviderEnum::choosesReasoningLevel()`).
        if ($provider->sends_reasoning_level) {
            $payload['reasoning_effort'] = 'high';
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
            $callback('error', 'Mistral ha cortado la respuesta con un error'.($message !== '' ? ": {$message}" : '.'));

            return false;
        }

        $choice = $data['choices'][0] ?? null;

        if (! is_array($choice)) {
            return true;
        }

        $content = $choice['delta']['content'] ?? null;

        if (is_string($content) && $content !== '') {
            $callback('content', $content);
        } elseif (is_array($content)) {
            foreach ($content as $chunk) {
                $this->handleChunk(is_array($chunk) ? $chunk : [], $callback);
            }
        }

        return match ((string) ($choice['finish_reason'] ?? '')) {
            'length', 'model_length' => $this->warn($callback, 'Mistral no ha terminado: la respuesta se ha cortado por larga. Revisa el final antes de aplicarlo.'),
            'error' => $this->fail($callback, 'Mistral no ha podido terminar la respuesta.'),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $chunk
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function handleChunk(array $chunk, Closure $callback): void
    {
        if (($chunk['type'] ?? null) === 'text' && is_string($chunk['text'] ?? null) && $chunk['text'] !== '') {
            $callback('content', $chunk['text']);

            return;
        }

        if (($chunk['type'] ?? null) !== 'thinking' || ! is_array($chunk['thinking'] ?? null)) {
            return;
        }

        foreach ($chunk['thinking'] as $thought) {
            if (is_array($thought) && is_string($thought['text'] ?? null) && $thought['text'] !== '') {
                $callback('thought', $thought['text']);
            }
        }
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
