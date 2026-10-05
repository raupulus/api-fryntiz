<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Verificación de captcha con dos proveedores posibles.
 *
 * Los formularios públicos aceptan el token de **Cloudflare Turnstile** o el de
 * **Google reCAPTCHA v3**, y cada cliente manda el que tiene montado. Este
 * servicio mira qué token ha llegado y lo verifica contra su proveedor;
 * `RecaptchaService` y `TurnstileService` siguen sabiendo cada uno lo suyo.
 *
 * Reglas, en este orden:
 *  1. Token de Turnstile y Turnstile configurado → se verifica en Cloudflare.
 *  2. Token de reCAPTCHA y reCAPTCHA configurado → se verifica en Google.
 *  3. Ningún proveedor configurado (desarrollo) → se da por válido, sin captcha.
 *  4. Algún proveedor configurado pero sin token utilizable → inválido.
 *
 * El punto 3 es sólo «ninguna clave en el entorno», no «ninguna clave del
 * proveedor que me han mandado». Si no, con reCAPTCHA configurado bastaría
 * mandar `turnstile_token=cualquier-cosa` para saltarse la comprobación.
 */
class CaptchaVerifierService
{
    /**
     * Campos del cuerpo que pueden llevar el token de Turnstile, por prioridad.
     * El primero es el que inyecta el widget de Cloudflare en un formulario.
     *
     * @var list<string>
     */
    public const TURNSTILE_FIELDS = ['cf-turnstile-response', 'turnstile_token'];

    /**
     * Campos del cuerpo que pueden llevar el token de reCAPTCHA, por prioridad.
     * `g-recaptcha-response` es el histórico y el que ya usan los clientes.
     *
     * @var list<string>
     */
    public const RECAPTCHA_FIELDS = ['g-recaptcha-response', 'recaptcha_token'];

    public function __construct(
        private readonly RecaptchaService $recaptcha,
        private readonly TurnstileService $turnstile,
    ) {}

    /**
     * ¿Hay algún proveedor de captcha configurado en este entorno?
     */
    public function isConfigured(): bool
    {
        return $this->recaptcha->isConfigured() || $this->turnstile->isConfigured();
    }

    /**
     * Verifica el captcha que venga en el cuerpo de la petición.
     *
     * @param  array<string,mixed>  $input  Cuerpo de la petición (o sólo lo validado).
     */
    public function verify(array $input, ?string $ip = null): CaptchaResult
    {
        $turnstileToken = $this->firstToken($input, self::TURNSTILE_FIELDS);

        if ($turnstileToken !== null && $this->turnstile->isConfigured()) {
            return $this->turnstile->verify($turnstileToken, $ip);
        }

        $recaptchaToken = $this->firstToken($input, self::RECAPTCHA_FIELDS);

        if ($recaptchaToken !== null && $this->recaptcha->isConfigured()) {
            return $this->recaptcha->verify($recaptchaToken, $ip);
        }

        if (! $this->isConfigured()) {
            return new CaptchaResult(valid: true, score: null, configured: false);
        }

        return new CaptchaResult(valid: false, score: null, configured: true);
    }

    /**
     * Primer campo de la lista que traiga un token no vacío.
     *
     * @param  array<string,mixed>  $input
     * @param  list<string>  $fields
     */
    private function firstToken(array $input, array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = $input[$field] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
