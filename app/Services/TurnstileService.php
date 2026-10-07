<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verificación de Cloudflare Turnstile.
 *
 * Es la alternativa a {@see RecaptchaService} en los formularios públicos.
 * Turnstile no da puntuación, sólo dice si el token es bueno o no, así que el
 * resultado lleva `score: null`: no hay umbral que aplicar ni bonus de
 * prioridad que calcular.
 *
 * Mismo criterio que reCAPTCHA (D10, ver docs/info/decisiones-tecnicas.md):
 * sin clave no se comprueba nada, y si Cloudflare no responde se deja pasar.
 */
class TurnstileService
{
    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * ¿Está configurado Turnstile en este entorno?
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.turnstile.secret_key'));
    }

    /**
     * Verifica un token y devuelve el resultado.
     *
     * Sin clave configurada (desarrollo) se da por válido y `configured` es
     * false, para que el que llama no lo confunda con una verificación real.
     */
    public function verify(?string $token, ?string $ip = null): CaptchaResult
    {
        $secret = config('services.turnstile.secret_key');

        if (empty($secret)) {
            return new CaptchaResult(valid: true, score: null, configured: false, provider: 'turnstile');
        }

        if (empty($token)) {
            return new CaptchaResult(valid: false, score: null, configured: true, provider: 'turnstile');
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::ENDPOINT, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // Fallo en abierto, igual que reCAPTCHA (D10): si Cloudflare no
            // responde no se puede afirmar que quien envía sea un bot. El
            // warning es la señal de alerta si aparece a ráfagas.
            Log::warning('Turnstile: could not verify', ['message' => $e->getMessage()]);

            return new CaptchaResult(valid: true, score: null, configured: true, provider: 'turnstile');
        }

        // Sólo un 5xx es «Cloudflare no puede contestar». Un 4xx es Cloudflare
        // diciendo que algo de **nuestra** petición está mal (token mal
        // formado, clave errónea), y eso no es motivo para dejar pasar.
        if ($response->serverError()) {
            Log::warning('Turnstile: unsatisfactory response', ['status' => $response->status()]);

            return new CaptchaResult(valid: true, score: null, configured: true, provider: 'turnstile');
        }

        $valid = $response->json('success') === true;

        if (! $valid) {
            $this->warnIfSecretIsWrong($response->json('error-codes'));
        }

        return new CaptchaResult(valid: $valid, score: null, configured: true, provider: 'turnstile');
    }

    /**
     * Una clave mal puesta rechaza a **todos** los visitantes sin más rastro
     * que un 422, así que ese caso concreto sí se deja en el log. El rechazo
     * de un token malo no se registra: un bot lo provocaría a voluntad y
     * ahogaría las alertas de verdad.
     *
     * @param  mixed  $errorCodes  Campo `error-codes` de la respuesta.
     */
    private function warnIfSecretIsWrong(mixed $errorCodes): void
    {
        $codes = is_array($errorCodes) ? $errorCodes : [];

        if (array_intersect($codes, ['missing-input-secret', 'invalid-input-secret'])) {
            Log::warning('Turnstile: the secret key was rejected, check TURNSTILE_SECRET_KEY', ['error_codes' => $codes]);
        }
    }
}
