<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre categorys.
 *
 * Los nombres de los métodos son los que llama el framework (`viewAny`,
 * `view`, `create`, `update`, `delete`…). Antes eran `index`, `store` y
 * `show`, que no los llama nadie: la policy parecía escrita y no se ejecutaba
 * ni una línea.
 *
 * Un editor necesita la taxonomía para poder clasificar lo que escribe, así que
 * puede crearla y editarla; borrarla queda para administración.
 */
class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->isSuperAdmin();
    }
}
