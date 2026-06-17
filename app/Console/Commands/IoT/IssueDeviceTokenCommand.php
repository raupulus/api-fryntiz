<?php

namespace App\Console\Commands\IoT;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Console\Command;

/**
 * Emite un token Sanctum para un dispositivo IoT concreto, con abilities
 * (scopes) limitadas y expiración opcional. El token se crea sobre el usuario
 * propietario del dispositivo y se nombra "device:{id}" para trazabilidad.
 *
 * Ejemplos:
 *   php artisan iot:device-token 12 --abilities=weatherstation:write
 *   php artisan iot:device-token 7 --abilities=energy:write --abilities=hardware:write --expires=365
 */
class IssueDeviceTokenCommand extends Command
{
    protected $signature = 'iot:device-token
        {device : ID del HardwareDevice}
        {--abilities=* : Abilities/scopes del token (ej. weatherstation:write)}
        {--expires= : Días hasta la expiración del token (vacío = sin expiración)}';

    protected $description = 'Emite un token Sanctum por dispositivo IoT con abilities limitadas';

    public function handle(): int
    {
        $device = HardwareDevice::find($this->argument('device'));

        if (! $device) {
            $this->error('Dispositivo hardware no encontrado.');

            return self::FAILURE;
        }

        $user = $device->user;

        if (! $user) {
            $this->error('El dispositivo no tiene usuario propietario asociado.');

            return self::FAILURE;
        }

        $abilities = $this->option('abilities');

        if (empty($abilities)) {
            $this->error('Debe indicar al menos una ability con --abilities (ej. weatherstation:write).');

            return self::FAILURE;
        }

        $expiresAt = $this->option('expires')
            ? now()->addDays((int) $this->option('expires'))
            : null;

        $token = $user->createToken("device:{$device->id}", $abilities, $expiresAt);

        $this->info('Token emitido correctamente para el dispositivo #'.$device->id);
        $this->line('Abilities: '.implode(', ', $abilities));
        $this->line('Expira: '.($expiresAt ? $expiresAt->toDateTimeString() : 'nunca'));
        $this->newLine();
        $this->warn('Guarda este token ahora (no se volverá a mostrar):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
