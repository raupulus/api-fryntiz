<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlatformEditorProfile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Los perfiles de editor se gestionan en la ficha de la plataforma, así que
 * los edita quien edita la plataforma (`PlatformPolicy::update`):
 * administración. Un editor sólo los elige en sus entradas.
 */
class PlatformEditorProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PlatformEditorProfile $profile): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PlatformEditorProfile $profile): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PlatformEditorProfile $profile): bool
    {
        return $user->isAdmin();
    }
}
