<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre plataformas (las webs que consumen la API).
 *
 * Crear, editar y borrar plataformas es cosa de administración. Un editor ve
 * únicamente las que tiene asignadas en `platform_user`, que son las mismas
 * sobre cuyo contenido puede trabajar.
 *
 * Los métodos se han renombrado a los que llama el framework: los antiguos
 * `index`, `store` y `show` no los invoca nadie.
 */
class PlatformPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function view(User $user, Platform $platform): bool
    {
        return $user->canManagePlatform((int) $platform->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Platform $platform): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Platform $platform): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Platform $platform): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Platform $platform): bool
    {
        return $user->isSuperAdmin();
    }
}
