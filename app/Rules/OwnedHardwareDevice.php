<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Hardware\HardwareDevice;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Laravel\Sanctum\PersonalAccessToken;

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
 * tokens "*" o tokens futuros ajenos a dispositivos— solo pasan la comprobación
 * de pertenencia por usuario. Como la regla únicamente se añade a los
 * FormRequest de escritura IoT, no afecta a otros endpoints ni tipos de token.
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
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if (! $token instanceof PersonalAccessToken) {
            return;
        }

        $deviceAbilities = $this->deviceAbilities((array) ($token->abilities ?? []));

        if ($deviceAbilities !== [] && ! in_array($deviceId, $deviceAbilities, true)) {
            $fail('El token utilizado no está autorizado para este dispositivo hardware.');
        }
    }

    /**
     * Extrae los ids de dispositivo declarados en las abilities ("device:{id}").
     *
     * @param  array<int, mixed>  $abilities
     * @return array<int, int>
     */
    private function deviceAbilities(array $abilities): array
    {
        $ids = [];

        foreach ($abilities as $ability) {
            if (is_string($ability) && str_starts_with($ability, 'device:')) {
                $ids[] = (int) substr($ability, strlen('device:'));
            }
        }

        return $ids;
    }
}
