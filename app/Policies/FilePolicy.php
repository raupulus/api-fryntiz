<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\File;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización centralizada para ficheros almacenados (`files`).
 *
 * Respeta el estado activo de la cuenta (`is_active`), prohíbe el acceso a
 * tokens de dispositivo IoT (Trampa 5) y aplica la frontera Admin vs SuperAdmin.
 */
class FilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->isAdmin()
            && ! TokenAbilities::deviceRequest($user);
    }

    public function view(User $user, File $file): bool
    {
        if (! $file->is_private) {
            return true;
        }

        return $this->canManage($user, $file);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, File $file): bool
    {
        return $this->canManage($user, $file);
    }

    public function delete(User $user, File $file): bool
    {
        return $this->canManage($user, $file);
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $user->is_active
            && $user->isSuperAdmin()
            && ! TokenAbilities::deviceRequest($user);
    }

    private function canManage(User $user, File $file): bool
    {
        if (! $user->is_active || TokenAbilities::deviceRequest($user)) {
            return false;
        }

        if ($file->user_id !== null && (int) $file->user_id === (int) $user->id) {
            return true;
        }

        if (! $user->isAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $file->user_id === null
            || ! User::query()
                ->whereKey((int) $file->user_id)
                ->where('role_id', UserRoleEnum::SuperAdmin->value)
                ->exists();
    }
}
