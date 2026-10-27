<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers\Concerns;

use App\Models\AiProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * Los errores HTTP de las API de IA dichos para quien redacta: los comparten
 * los conectores de texto y los de imágenes.
 */
trait DescribesApiErrors
{
    /**
     * El mensaje y el código de un error HTTP. Cada API lo escribe a su
     * manera: `error.message` (Google, OpenAI), `error` como texto (xAI),
     * `message` o `detail` (Mistral).
     *
     * @return array{0: string, 1: string|null}
     */
    protected function apiError(Response $response): array
    {
        $json = $response->json();
        $json = is_array($json) ? $json : [];
        $error = $json['error'] ?? null;
        $baseResp = $json['base_resp'] ?? null;

        $message = match (true) {
            is_array($baseResp) && isset($baseResp['status_code']) && (int) $baseResp['status_code'] !== 0 => (string) ($baseResp['status_msg'] ?? ''),
            is_array($error) && is_string($error['message'] ?? null) => $error['message'],
            is_string($error) => $error,
            is_string($json['message'] ?? null) => $json['message'],
            is_string($json['detail'] ?? null) => $json['detail'],
            default => Str::limit(strip_tags($response->body()), 300),
        };

        $code = match (true) {
            is_array($baseResp) && isset($baseResp['status_code']) && (int) $baseResp['status_code'] !== 0 => (string) $baseResp['status_code'],
            is_array($error) && is_scalar($error['code'] ?? null) => (string) $error['code'],
            default => null,
        };

        return [$message, $code];
    }

    /**
     * Si el error habla de saldo o de límite de gasto.
     */
    protected function isAboutCredit(string $message): bool
    {
        return (bool) preg_match('/\\bcredits?\\b|spending limit|insufficient (?:funds|balance|quota)|billing/i', $message);
    }

    /**
     * Lo que se le dice a quien redacta cuando el proveedor responde con un
     * error.
     */
    protected function errorMessage(AiProvider $provider, int $status, string $message, ?string $code, ?string $model = null): string
    {
        $label = $provider->provider->label();
        $model ??= $provider->model;

        return match (true) {
            // Google lo comprueba antes que la propia petición; OpenAI lo dice
            // con un 429 y el código `insufficient_quota`, y xAI con un 403 y
            // «used all available credits or reached its monthly spending
            // limit» (probado el 2026-10-17): no es que la clave no valga.
            $status === 402 || $code === 'insufficient_quota' || $this->isAboutCredit($message) => "La cuenta de {$label} no tiene saldo o ha llegado a su límite de gasto. Recárgalo en {$provider->provider->billingPlace()} y vuelve a intentarlo.",
            $status === 429 => "Se ha agotado la cuota de {$label} de esta clave. Espera un poco y vuelve a intentarlo.",
            in_array($status, [401, 403], true) => "La clave de {$label} no vale o no tiene permiso para usar la API ({$message}).",
            $status === 404 => "El modelo {$model} no está disponible para esta clave de {$label} ({$message}).",
            $status >= 500 => "{$label} no está disponible ahora mismo. Vuelve a intentarlo en un momento.",
            default => "{$label} ha rechazado la petición ({$status}): {$message}",
        };
    }
}
