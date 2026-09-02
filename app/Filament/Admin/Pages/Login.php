<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Concerns\HasRecaptchaLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    use HasRecaptchaLogin;

    /**
     * Referencia del intento para el log, sin la dirección completa.
     *
     * Antes se escribía el email tal cual en cada intento, con éxito o sin él.
     * Un servidor con escaneo constante acababa con el log lleno de direcciones
     * de correo, que es un dato personal guardado durante 14 días sin
     * necesitarlo (auditoría AR-S05).
     *
     * Con el hash corto y el dominio se sigue pudiendo correlacionar intentos
     * —«éstos son todos de la misma cuenta»— y saber de dónde vienen, que es
     * para lo que se mira un log de accesos. La dirección exacta, si hace
     * falta, está en la tabla de usuarios.
     */
    private function accountReference(): string
    {
        $email = mb_strtolower(trim((string) ($this->data['email'] ?? '')));

        if ($email === '') {
            return 'sin email';
        }

        $dominio = str_contains($email, '@') ? mb_substr($email, (int) mb_strpos($email, '@')) : '';

        return mb_substr(hash('sha256', $email), 0, 12).$dominio;
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->verifyRecaptcha();

            $result = parent::authenticate();

            Log::info('[Admin Login] Successful authentication', [
                'user' => $this->accountReference(),
            ]);

            return $result;
        } catch (ValidationException $e) {
            // Esta rama es el «credenciales incorrectas» de toda la vida: se
            // relanza para que Filament pinte el error del formulario.
            Log::warning('[Admin Login] Failed credentials', [
                'user' => $this->accountReference(),
                'errors' => array_keys($e->errors()),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // Sin el stack trace: Laravel ya escribe la excepción completa por
            // su cuenta, y aquí sólo servía para duplicar el volcado dentro del
            // log de accesos.
            Log::error('[Admin Login] Unexpected error: '.$e->getMessage(), [
                'user' => $this->accountReference(),
            ]);

            // El mensaje que ve quien intenta entrar es genérico. El
            // getMessage() de una excepción interna puede describir la
            // aplicación por dentro, y la pantalla de login es pública.
            $this->addError('data.email', __('auth_panel.login_failed'));

            return null;
        }
    }
}
