<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Services\RecaptchaService;

/**
 * reCAPTCHA v3 en el login de un panel Filament.
 *
 * El token lo rellena el JS de `resources/views/filament/components/recaptcha-login-script.blade.php`
 * (inyectado vía render hook `AUTH_LOGIN_FORM_BEFORE`) en la propiedad pública
 * `recaptchaToken`, fuera del schema del formulario para no interferir con
 * `$this->form->getState()`.
 *
 * Misma regla que `ContactSendRequest`/`ContactMessageController`: sin
 * `RECAPTCHA_SECRET_KEY` en el entorno (desarrollo) no se aplica ninguna
 * comprobación; con la clave puesta (producción) un token inválido corta el
 * login con el mismo mensaje genérico de credenciales incorrectas, sin
 * distinguir «bloqueado por captcha» de «contraseña mala».
 */
trait HasRecaptchaLogin
{
    public ?string $recaptchaToken = null;

    protected function verifyRecaptcha(): void
    {
        $result = app(RecaptchaService::class)->verify($this->recaptchaToken, request()->ip());

        if ($result->configured && ! $result->valid) {
            $this->throwFailureValidationException();
        }
    }
}
