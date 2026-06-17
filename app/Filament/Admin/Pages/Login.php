<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            Log::info('[Admin Login] Intento de autenticación', [
                'email' => $this->data['email'] ?? 'sin email',
                'ip' => request()->ip(),
            ]);

            $result = parent::authenticate();

            Log::info('[Admin Login] Autenticación exitosa', [
                'email' => $this->data['email'] ?? 'sin email',
            ]);

            return $result;
        } catch (ValidationException $e) {
            Log::warning('[Admin Login] Fallo de validación/credenciales', [
                'email' => $this->data['email'] ?? 'sin email',
                'errors' => $e->errors(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('[Admin Login] Error inesperado: '.$e->getMessage(), [
                'email' => $this->data['email'] ?? 'sin email',
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('data.email', 'Error interno: '.$e->getMessage());

            return null;
        }
    }
}
