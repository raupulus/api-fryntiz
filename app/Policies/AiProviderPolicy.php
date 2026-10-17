<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Los proveedores de IA, con sus claves, son sólo del superadmin: ni un
 * administrador los ve. Lo que usa cada plataforma (su instrucción y sus
 * perfiles de editor) se edita en la plataforma.
 *
 * `Gate::before` ya deja pasar al superadmin; se repite aquí para que la
 * policy diga lo mismo leída sola.
 */
class AiProviderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, AiProvider $provider): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, AiProvider $provider): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, AiProvider $provider): bool
    {
        return $user->isSuperAdmin();
    }
}
