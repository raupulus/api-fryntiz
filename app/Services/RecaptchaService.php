<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verificación de reCAPTCHA.
 *
 * Devuelve la **puntuación**, no sólo un sí/no. reCAPTCHA v3 no dice «humano» o
 * «bot»: da un número de 0.0 a 1.0 y el que decide el corte es quien lo usa.
 * Antes se descartaba ese número y se guardaba un booleano, así que la columna
 * `captcha_score` de la tabla `emails` no se llenaba nunca y no había forma de
 * revisar por qué se había descartado un mensaje.
 */
class RecaptchaService
{
    /**
     * ¿Está configurado reCAPTCHA en este entorno?
     */
    public function isConfigured(): bool
    {
        return ! empty(config('google.recaptcha.secret_key'));
    }

    /**
     * Verifica un token y devuelve el resultado con su puntuación.
     *
     * Sin claves configuradas (desarrollo) se da por válido, pero **sin
     * puntuación**: así el que llama sabe que no hay señal de captcha y no
     * confunde «no configurado» con «puntuación perfecta».
     *
     * @param  float|null  $minScore  Umbral propio. Sin él manda
     *                                `google.recaptcha.min_score`. El login de
     *                                los paneles pasa el suyo, más permisivo.
     */
    public function verify(?string $token, ?string $ip = null, ?float $minScore = null, ?string $expectedAction = null): CaptchaResult
    {
        $secret = config('google.recaptcha.secret_key');

        if (empty($secret)) {
            return new CaptchaResult(valid: true, score: null, configured: false, provider: 'recaptcha');
        }

        if (empty($token)) {
            return new CaptchaResult(valid: false, score: null, configured: true, provider: 'recaptcha');
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // FALLO EN ABIERTO — decisión tomada, no un descuido (SEC-05 / D10).
            Log::warning('reCAPTCHA: could not verify token', ['message' => $e->getMessage()]);

            return new CaptchaResult(valid: true, score: null, configured: true, provider: 'recaptcha');
        }

        // Igual que TurnstileService: sólo un 5xx es fallo del proveedor (fail-open D10).
        // Un 4xx indica petición inválida y debe rechazarse en cerrado.
        if ($response->serverError()) {
            Log::warning('reCAPTCHA: unsatisfactory upstream response', ['status' => $response->status()]);

            return new CaptchaResult(valid: true, score: null, configured: true, provider: 'recaptcha');
        }

        if (! $response->successful()) {
            Log::warning('reCAPTCHA: client error response', ['status' => $response->status()]);

            return new CaptchaResult(valid: false, score: null, configured: true, provider: 'recaptcha');
        }

        $valid = $response->json('success') === true;
        $score = $response->json('score');
        $score = is_numeric($score) ? (float) $score : null;

        if ($valid && $expectedAction !== null) {
            $actualAction = $response->json('action');
            if (! is_string($actualAction) || $actualAction !== $expectedAction) {
                Log::warning('reCAPTCHA: action mismatch', [
                    'expected' => $expectedAction,
                    'actual' => is_string($actualAction) ? $actualAction : null,
                ]);
                $valid = false;
            }
        }

        if ($valid && $score !== null) {
            $valid = $score >= ($minScore ?? (float) config('google.recaptcha.min_score', 0.5));
        }

        return new CaptchaResult(
            valid: $valid,
            score: $score,
            configured: true,
            provider: 'recaptcha',
        );
    }
}
