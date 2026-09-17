<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización para impresoras y sus trabajos en cola.
 *
 * La propiedad reside en el HardwareDevice anfitrión. Un trabajo en cola hereda
 * los permisos de su impresora: quien no alcanza el periférico no alcanza sus
 * trabajos.
 *
 * Los tokens IoT ligados a un dispositivo (`device:{id}`) pueden operar
 * exclusivamente las impresoras conectadas a dicho dispositivo.
 */
class PrinterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Printer|PrinterStack $record): bool
    {
        return $this->isOwnedAndReachable($user, $record);
    }

    public function create(User $user): bool
    {
        // La creación de nuevas impresoras es una acción administrativa o de usuario con sesión
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, Printer|PrinterStack $record): bool
    {
        return $this->isOwnedAndReachable($user, $record);
    }

    public function delete(User $user, Printer|PrinterStack $record): bool
    {
        // Borrar una impresora o cancelar/eliminar trabajos es potestad humana
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        $printer = $record instanceof PrinterStack ? $record->printer : $record;
        if (! $printer || ! $printer->hardwareDevice) {
            return false;
        }

        return (int) $printer->hardwareDevice->user_id === (int) $user->id || $user->isAdmin();
    }

    public function restore(User $user, Printer|PrinterStack $record): bool
    {
        return $this->delete($user, $record);
    }

    public function forceDelete(User $user, Printer|PrinterStack $record): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * Comprueba si el usuario o dispositivo tiene acceso legítimo a la impresora o trabajo.
     */
    protected function isOwnedAndReachable(User $user, Printer|PrinterStack $record): bool
    {
        $printer = $record instanceof PrinterStack ? $record->printer : $record;

        if (! $printer || ! $printer->hardwareDevice) {
            return false;
        }

        $device = $printer->hardwareDevice;

        if (TokenAbilities::deviceRequest($user)) {
            return (int) $device->user_id === (int) $user->id
                && TokenAbilities::tokenReachesDevice($user, (int) $device->id);
        }

        return (int) $device->user_id === (int) $user->id || $user->isAdmin();
    }
}
