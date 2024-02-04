<?php

namespace App\Policies;

use App\Models\Content\Content;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class TagPolicy
 */
class ContentPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return RoleHelper::isAdmin($user->role_id);
    }

    public function edit(User $user, Content $model): bool
    {
        return RoleHelper::isAdmin($user->role_id) ?? $user->id ===  $model->author_id;
    }

    public function store(User $user): bool
    {
        return RoleHelper::isAdmin($user->role_id);
    }

    public function delete(User $user, Content $content): bool
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $content->user_id === $user->id;
    }

    public function show(User $user, Content $tag): bool
    {
        return true;
    }

    public function update(User $user, Content $content): bool
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $content->user_id === $user->id;
    }


    /**
     * Permite actualizar el seo de un contenido.
     *
     * @param User $user Usuario autenticado.
     * @param Content $content Contenido a actualizar.
     *
     * @return bool
     */
    public function updateSeo(User $user, Content $content): bool
    {
        return RoleHelper::isAdmin($user->role_id) ?? $user->id ===  $content->author_id;
    }

    /**
     * Permite actualizar los metadatos de un contenido.
     *
     * @param User $user Usuario autenticado.
     * @param Content $content Contenido a actualizar.
     *
     * @return bool
     */
    public function updateMetadata(User $user, Content $content): bool
    {
        return RoleHelper::isAdmin($user->role_id) ?? $user->id ===  $content->author_id;
    }
}
