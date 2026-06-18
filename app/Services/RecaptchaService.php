<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Servicio de integración con la API de Google reCAPTCHA.
 */
class RecaptchaService
{
    /**
     * Verifica la validez de un token generado por reCAPTCHA a través de su endpoint oficial.
     *
     * @param string $token Token proveído por el frontend tras superar el desafío.
     * @return bool True si es válido y humano, False en caso contrario.
     */
    public function verify(string $token): bool
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $token,
        ]);

        return $response->successful() && $response->json('success') === true;
    }
}
