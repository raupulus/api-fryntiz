<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Content\Content;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre contenidos.
 *
 * El eje que faltaba: **la plataforma**. Hasta ahora el permiso era global —o
 * eras admin y tocabas todo, o eras el autor—, así que no había forma de tener
 * a alguien que escriba en una web y no en las otras. Ahora un editor sólo
 * alcanza los contenidos de las plataformas que tiene asignadas en
 * `platform_user`.
 *
 * Un contenido sin plataforma (`platform_id = null`) es de administración
 * general: ningún editor llega a él.
 */
class ContentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function view(User $user, Content $content): bool
    {
        return $this->reaches($user, $content);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function update(User $user, Content $content): bool
    {
        return $this->reaches($user, $content);
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->isAdmin()
            || ($this->reaches($user, $content) && $this->isAuthor($user, $content));
    }

    public function restore(User $user, Content $content): bool
    {
        return $this->delete($user, $content);
    }

    public function forceDelete(User $user, Content $content): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Alcance real sobre un contenido: administración, autoría, o edición
     * dentro de una plataforma asignada.
     */
    private function reaches(User $user, Content $content): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isAuthor($user, $content)) {
            return true;
        }

        return $user->canManagePlatform(
            $content->platform_id === null ? null : (int) $content->platform_id
        );
    }

    /**
     * Autoría del contenido.
     *
     * La columna es `author_id`, no `user_id`. Aquí se leía `$content->user_id`,
     * que en `contents` no existe: siempre valía null, así que la comparación
     * con el id del usuario nunca se cumplía y un autor NO alcanzaba su propio
     * contenido — sólo llegaba a él si además era admin o tenía la plataforma
     * asignada. En `delete()` el efecto era total: nadie que no fuese admin
     * podía borrar lo suyo.
     *
     * PHPStan ya lo señalaba; estaba silenciado en el baseline.
     */
    private function isAuthor(User $user, Content $content): bool
    {
        return $content->author_id !== null
            && (int) $content->author_id === (int) $user->id;
    }
}
