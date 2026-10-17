<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use App\Services\Ai\AiTextProvider;
use App\Services\Ai\Providers\Concerns\DescribesApiErrors;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Lo común de los conectores: la petición en streaming, la lectura de los
 * eventos SSE, los errores HTTP y la prueba de conexión.
 *
 * La clave nunca va en la URL: los errores de conexión de Guzzle llevan la URL
 * completa, y ese mensaje acababa en el modal y en el log.
 */
abstract class HttpTextProvider implements AiTextProvider
{
    use DescribesApiErrors;

    /**
     * La petición: devuelve la URL y el cuerpo.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    abstract protected function request(AiProvider $provider, string $systemInstruction, string $prompt, ?AiReasoningLevelEnum $level): array;

    /**
     * Las cabeceras de autenticación con la clave.
     *
     * @return array<string, string>
     */
    abstract protected function authHeaders(string $apiKey): array;

    /**
     * Un evento ya decodificado. Devuelve `false` para dejar de leer (después
     * de un `error`).
     *
     * @param  array<string, mixed>  $data
     * @param  Closure(string $type, string $text): void  $callback
     */
    abstract protected function handleEvent(AiProvider $provider, array $data, Closure $callback): bool;

    public function stream(
        AiProvider $provider,
        string $systemInstruction,
        string $prompt,
        ?AiReasoningLevelEnum $level,
        Closure $callback,
    ): void {
        $label = $provider->provider->label();
        $apiKey = $provider->api_key;

        if (blank($apiKey)) {
            $callback('error', "La clave de {$label} no está puesta o no se puede leer. Vuelve a pegarla en Configuración → Proveedores de IA.");

            return;
        }

        [$url, $payload] = $this->request($provider, $systemInstruction, $prompt, $level);

        try {
            $response = $this->http()->withHeaders($this->authHeaders($apiKey))->post($url, $payload);

            if ($response->failed()) {
                [$message, $code] = $this->apiError($response);

                Log::warning("{$label}: error de la API", ['status' => $response->status(), 'code' => $code, 'error' => $message]);
                $callback('error', $this->errorMessage($provider, $response->status(), $message, $code));

                return;
            }

            $this->readEvents($provider, $response, $callback);
        } catch (Throwable $e) {
            Log::error("{$label}: fallo de conexión: ".$e->getMessage(), ['exception' => $e]);
            $callback('error', "No se ha podido conectar con {$label}. Vuelve a intentarlo en un momento.");
        }
    }

    public function probe(AiProvider $provider): array
    {
        $error = null;
        $text = '';

        $this->stream(
            $provider,
            'Esto es una prueba de conexión. Responde en español y en una sola línea.',
            'Responde sólo con la palabra «OK».',
            // El nivel más ligero que admite: la prueba no necesita pensar.
            $provider->sends_reasoning_level ? $provider->provider->reasoningLevels()[0] : null,
            function (string $type, string $chunk) use (&$error, &$text): void {
                if ($type === 'error') {
                    $error ??= $chunk;
                } elseif ($type === 'content') {
                    $text .= $chunk;
                }
            },
        );

        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        if (trim($text) === '') {
            return ['ok' => false, 'message' => 'Ha respondido, pero sin ningún texto.'];
        }

        return ['ok' => true, 'message' => Str::limit(trim($text), 120)];
    }

    protected function http(): PendingRequest
    {
        return Http::withOptions(['stream' => true, 'timeout' => 180, 'connect_timeout' => 15]);
    }

    /**
     * Lee el cuerpo por bloques SSE (separados por una línea en blanco) y
     * pasa el JSON de cada `data:` a `handleEvent()`. `[DONE]` cierra el
     * stream en las API que lo mandan.
     *
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function readEvents(AiProvider $provider, Response $response, Closure $callback): void
    {
        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            if (connection_aborted()) {
                return;
            }

            $buffer .= str_replace("\r\n", "\n", $body->read(1024));

            while (($position = strpos($buffer, "\n\n")) !== false) {
                if (! $this->processBlock($provider, substr($buffer, 0, $position), $callback)) {
                    return;
                }

                $buffer = substr($buffer, $position + 2);
            }
        }

        if (trim($buffer) !== '' && ! connection_aborted()) {
            $this->processBlock($provider, $buffer, $callback);
        }
    }

    /**
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function processBlock(AiProvider $provider, string $block, Closure $callback): bool
    {
        foreach (explode("\n", trim($block)) as $line) {
            $line = trim($line);

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $json = trim(substr($line, 5));

            if ($json === '[DONE]') {
                return false;
            }

            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (is_array($data) && ! $this->handleEvent($provider, $data, $callback)) {
                return false;
            }
        }

        return true;
    }
}
