<?php

declare(strict_types=1);

namespace App\Console\Commands\IoT;

use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\DeviceTokenService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Emite un token Sanctum para un dispositivo IoT concreto, con abilities
 * (scopes) limitadas y expiración opcional. El token se crea sobre el usuario
 * propietario del dispositivo y se nombra "device:{id}" para trazabilidad.
 *
 * Ejemplos:
 *   php artisan iot:device-token 12 --abilities=weatherstation:write
 *   php artisan iot:device-token 7 --abilities=hardware:write --abilities=hardware:read --expires=365
 *
 * Abilities válidas (catálogo en App\Support\Auth\TokenAbilities):
 *   hardware:read, hardware:write, weatherstation:write, keycounter:write,
 *   smartplant:write, airflight:write.
 * No existe comodín: "*" y "session" se rechazan.
 */
class IssueDeviceTokenCommand extends Command
{
    protected $signature = 'iot:device-token
        {device : ID del HardwareDevice}
        {--abilities=* : Abilities/scopes del token (ej. weatherstation:write)}
        {--expires= : Días hasta la expiración del token (vacío = sin expiración)}';

    protected $description = 'Emite un token Sanctum por dispositivo IoT con abilities limitadas';

    public function handle(DeviceTokenService $service): int
    {
        $device = HardwareDevice::find($this->argument('device'));

        if (! $device) {
            $this->error('Dispositivo hardware no encontrado.');

            return self::FAILURE;
        }

        $abilities = $this->option('abilities');

        $expiresAt = $this->option('expires')
            ? now()->addDays((int) $this->option('expires'))
            : null;

        try {
            $token = $service->issue($device, $abilities, $expiresAt);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Token emitido correctamente para el dispositivo #'.$device->id);
        $this->line('Abilities: '.implode(', ', $token->accessToken->abilities ?? $abilities));
        $this->line('Expira: '.($expiresAt ? $expiresAt->toDateTimeString() : 'nunca'));
        $this->newLine();
        $this->warn('Guarda este token ahora (no se volverá a mostrar):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
