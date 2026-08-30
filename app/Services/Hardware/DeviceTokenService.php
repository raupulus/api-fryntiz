<?php

declare(strict_types=1);

namespace App\Services\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Support\Auth\TokenAbilities;
use Carbon\Carbon;
use InvalidArgumentException;
use Laravel\Sanctum\NewAccessToken;
use RuntimeException;

/**
 * Emisión de tokens Sanctum ligados a un dispositivo IoT concreto.
 *
 * Fuente única usada tanto por el comando `iot:device-token` como por el
 * panel Filament. Siempre añade la ability "device:{id}" para ligar el token
 * al dispositivo (validación estricta en escritura) y lo crea sobre el
 * usuario propietario, con nombre "device:{id}" para trazabilidad.
 *
 * El catálogo de abilities vive en {@see TokenAbilities}. Aquí sólo se emiten
 * abilities de módulo: un token de dispositivo **nunca** lleva
 * {@see TokenAbilities::SESSION} ni el comodín `*`.
 */
class DeviceTokenService
{
    /**
     * Abilities (scopes) de módulo disponibles para tokens de dispositivo.
     * No incluye la ability "device:{id}", que se añade automáticamente.
     *
     * @var array<string, string>
     */
    public const MODULE_ABILITIES = TokenAbilities::MODULE_ABILITIES;

    /**
     * Emite un token ligado al dispositivo.
     *
     * @param  array<int, string>  $abilities  Abilities de módulo (sin "device:{id}").
     *
     * @throws RuntimeException Si el dispositivo no tiene usuario propietario.
     * @throws InvalidArgumentException Si no se indica ninguna ability de módulo
     *                                  o si se cuela una ability no permitida.
     */
    public function issue(HardwareDevice $device, array $abilities, ?Carbon $expiresAt = null): NewAccessToken
    {
        $user = $device->user;

        if (! $user) {
            throw new RuntimeException('El dispositivo no tiene usuario propietario asociado.');
        }

        $abilities = array_values(array_unique(array_filter($abilities)));

        if (empty($abilities)) {
            throw new InvalidArgumentException('Debe indicar al menos una ability de módulo (ej. hardware:write).');
        }

        // Un token de dispositivo sólo puede llevar abilities del catálogo de
        // módulo. Ni "*", ni "session", ni nada inventado.
        $invalid = array_diff($abilities, array_keys(TokenAbilities::MODULE_ABILITIES));

        if ($invalid !== []) {
            throw new InvalidArgumentException(
                'Abilities no permitidas para un token de dispositivo: '.implode(', ', $invalid).'.'
            );
        }

        // Liga el token al dispositivo de forma estricta.
        $abilities[] = $this->deviceAbility($device);

        return $user->createToken("device:{$device->id}", $abilities, $expiresAt);
    }

    /**
     * Ability que liga un token a un dispositivo concreto.
     */
    public function deviceAbility(HardwareDevice $device): string
    {
        return TokenAbilities::forDevice($device);
    }
}
