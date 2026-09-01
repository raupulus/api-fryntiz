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
     */
    public function verify(?string $token, ?string $ip = null): CaptchaResult
    {
        $secret = config('google.recaptcha.secret_key');

        if (empty($secret)) {
            return new CaptchaResult(valid: true, score: null, configured: false);
        }

        if (empty($token)) {
            return new CaptchaResult(valid: false, score: null, configured: true);
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // FALLO EN ABIERTO — decisión tomada, no un descuido (SEC-05).
            //
            // Si Google no responde no se puede afirmar que quien envía sea un
            // bot, y no se va a cerrar el acceso al sitio porque un tercero se
            // haya caído. En principio no debería pasar; si pasa y resulta ser
            // un problema real, la salida es buscar otro proveedor, no dejar a
            // la gente fuera mientras tanto.
            //
            // Este warning y el de más abajo son la señal de alerta: si
            // aparecen a ráfagas en el log, alguien está provocando el fallo
            // para saltarse la comprobación. Está documentado en
            // docs/info/auth.md y en docs/info/decisiones-tecnicas.md.
            Log::warning('reCAPTCHA: no se ha podido verificar', ['message' => $e->getMessage()]);

            return new CaptchaResult(valid: true, score: null, configured: true);
        }

        if (! $response->successful()) {
            // Mismo criterio que arriba: fallo en abierto deliberado.
            Log::warning('reCAPTCHA: respuesta no satisfactoria', ['status' => $response->status()]);

            return new CaptchaResult(valid: true, score: null, configured: true);
        }

        $valid = $response->json('success') === true;
        $score = $response->json('score');

        return new CaptchaResult(
            valid: $valid,
            score: is_numeric($score) ? (float) $score : null,
            configured: true,
        );
    }
}
