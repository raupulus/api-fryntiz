<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Como el cast `encrypted`, pero un valor que no se puede descifrar se lee
 * como `null` en vez de lanzar una excepción.
 *
 * Pasa cuando la `APP_KEY` ha cambiado desde que se guardó: en desarrollo
 * `project:clear` la regenera cada vez (D15), y en producción la rota
 * `project:clear --key`. Con `encrypted`, abrir la ficha daba un 500 y, al
 * pegar una clave nueva, Eloquent intentaba descifrar la vieja para comparar
 * y volvía a fallar. Así se puede ver que no se lee y volver a pegarla.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Crypt::encryptString((string) $value);
    }
}
