<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Hardware\HardwareDevice;
use App\Support\Auth\TokenAbilities;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function auth;

/**
 * Verifica la pertenencia del dispositivo hardware indicado en una escritura IoT:
 *
 *  1. Pertenencia por usuario: el dispositivo debe pertenecer al usuario
 *     autenticado (su `user_id`).
 *  2. Ligado estricto por dispositivo: si el token declara dispositivos
 *     concretos mediante abilities "device:{id}", el dispositivo indicado debe
 *     coincidir con uno de ellos.
 *
 * Solo se aplica el ligado estricto a tokens relacionados con dispositivos
 * (los que tienen alguna ability "device:*"). Los tokens sin esa ability —p. ej.
 * una sesión humana— solo pasan la comprobación de pertenencia por usuario.
 *
 * La existencia del dispositivo se delega en la regla `exists` del FormRequest.
 */
class OwnedHardwareDevice implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        if (! $user) {
            $fail('No estás autenticado para escribir datos de dispositivos.');

            return;
        }

        // Si no es un id válido, deja que la regla "exists" emita el error.
        if (! is_numeric($value)) {
            return;
        }

        $deviceId = (int) $value;
        $device = HardwareDevice::query()->find($deviceId);

        // El dispositivo no existe: lo informa la regla "exists".
        if (! $device) {
            return;
        }

        // 1) Pertenencia por usuario.
        if ((int) $device->user_id !== (int) $user->id) {
            $fail('El dispositivo hardware indicado no pertenece a tu cuenta.');

            return;
        }

        // 2) Ligado estricto por dispositivo (solo si el token lo declara).
        if (! TokenAbilities::tokenReachesDevice($user, $deviceId)) {
            $fail('El token utilizado no está autorizado para este dispositivo hardware.');
        }
    }
}
