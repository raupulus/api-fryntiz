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

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->verifyRecaptcha();

            $result = parent::authenticate();

            Log::info('[Admin Login] Autenticación exitosa', [
                'email' => $this->data['email'] ?? 'sin email',
            ]);

            return $result;
        } catch (ValidationException $e) {
            // Esta rama es el «credenciales incorrectas» de toda la vida: se
            // relanza para que Filament pinte el error del formulario.
            Log::warning('[Admin Login] Fallo de validación/credenciales', [
                'email' => $this->data['email'] ?? 'sin email',
                'errors' => $e->errors(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // Sin el stack trace: Laravel ya escribe la excepción completa por
            // su cuenta, y aquí sólo servía para duplicar el volcado dentro del
            // log de accesos.
            Log::error('[Admin Login] Error inesperado: '.$e->getMessage(), [
                'email' => $this->data['email'] ?? 'sin email',
            ]);

            // El mensaje que ve quien intenta entrar es genérico. El
            // getMessage() de una excepción interna puede describir la
            // aplicación por dentro, y la pantalla de login es pública.
            $this->addError('data.email', __('auth_panel.login_failed'));

            return null;
        }
    }
}
