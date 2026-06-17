<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug\Concerns;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;

/**
 * Trait para los comandos de seed de debug.
 *
 * Resuelve dinámicamente los IDs de `hardware_device` y `user` para evitar
 * inserciones huérfanas en entornos donde no exista el registro con id=1.
 * Aborta el comando con código de salida 1 y un mensaje claro si no encuentra
 * ningún registro.
 */
trait ResolvesDebugDefaults
{
    /**
     * Devuelve el ID del primer HardwareDevice disponible o aborta el comando.
     *
     * @return int|null Null si no hay ninguno (en cuyo caso el comando debe abortar).
     */
    protected function resolveHardwareDeviceId(): ?int
    {
        $device = HardwareDevice::query()->orderBy('id')->first();

        if (! $device) {
            $this->error('❌ No existe ningún HardwareDevice en la base de datos.');
            $this->warn('   Crea uno con `php artisan debug:seed-energy` o desde el panel /admin.');

            return null;
        }

        if ($device->id !== 1) {
            $this->warn("⚠️  No existe HardwareDevice id=1. Usando id={$device->id} ({$device->name}).");
        }

        return $device->id;
    }

    /**
     * Devuelve el ID del primer User disponible o aborta el comando.
     *
     * @return int|null Null si no hay ninguno.
     */
    protected function resolveUserId(): ?int
    {
        $user = User::query()->orderBy('id')->first();

        if (! $user) {
            $this->error('❌ No existe ningún User en la base de datos.');
            $this->warn('   Crea uno con `php artisan tinker` o ejecuta los seeders por defecto.');

            return null;
        }

        if ($user->id !== 1) {
            $this->warn("⚠️  No existe User id=1. Usando id={$user->id} ({$user->email}).");
        }

        return $user->id;
    }

    /**
     * Aborta si APP_ENV es 'production'.
     *
     * @return bool True si se puede continuar, false si debe abortar.
     */
    protected function guardEnvironment(): bool
    {
        if (app()->environment('production')) {
            $this->error('❌ Este comando NO puede ejecutarse en producción.');

            return false;
        }

        // Evitar fallos de broadcasting (como Pusher no instalado) durante el seeding de debug
        config(['broadcasting.default' => 'null']);

        return true;
    }
}
