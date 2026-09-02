<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Editar a otro usuario desde el recurso de administración.
     *
     * ⚠️ **Editarse a uno mismo NO pasa por aquí, y es deliberado (AR-P01).**
     *
     * Esto devolvía `true` para el propio registro (`$user->id === $model->id`),
     * y el formulario del recurso incluye `role_id`. Con las dos cosas juntas,
     * un `Admin` entraba en `/admin/users/{su_id}/edit`, se ponía `SuperAdmin` y
     * se llevaba el bypass total de `Gate::before`. Está reproducido en
     * `tests/Unit/Policies/UserPolicyTest.php`.
     *
     * El autoservicio tiene su propia página y no necesita esta policy:
     * `App\Filament\Admin\Pages\Profile` y `App\Filament\Tenant\Pages\EditProfile`,
     * las dos sobre `EditsOwnProfile`, que deja cambiar nombre, apodo,
     * profesión, ajustes y contraseña —pidiendo la actual— y **no expone ni
     * `role_id` ni `is_active`**.
     *
     * Que un administrador no pueda editarse desde el listado de usuarios no es
     * una pérdida: para eso está «Editar perfil» en su menú.
     */
    public function update(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        // El propio registro no se toca desde el recurso de usuarios. Es la
        // misma regla que D91 aplica al email —«cambiarse el propio email es la
        // vía clásica para apropiarse de una cuenta»— extendida al resto del
        // formulario, que incluye `role_id` e `is_active`.
        //
        // Un SuperAdmin sí llega, pero no por aquí: `Gate::before` le concede
        // todo antes de evaluar esta policy. Y en su caso no hay nada que
        // escalar, ya está arriba del todo.
        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
