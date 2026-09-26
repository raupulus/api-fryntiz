<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Content\Content;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre contenidos (B3 y DUDA-2 del plan de contenidos del
 * 2026-09-24).
 *
 * - Administrador y SuperAdmin: todo.
 * - Editor **autor**: control completo de lo suyo (editar, publicar, eliminar,
 *   cambiar autor, plataforma y colaboradores).
 * - Editor **colaborador**: edita datos, SEO, taxonomías, páginas e imágenes, en
 *   cualquier plataforma. No publica al momento (programa con al menos una
 *   semana de margen, para que un administrador lo revise), no elimina, y no
 *   cambia autor, plataforma ni colaboradores.
 * - Las plataformas asignadas a un Editor (`platform_user`) dicen **dónde puede
 *   crear**, no qué puede editar: con la plataforma y sin relación con un
 *   contenido, no llega a él. Antes bastaba la plataforma para editar lo de
 *   cualquiera.
 *
 * Un colaborador quitado (fila borrada del pivote) ya no cuenta: ver
 * `Content::contributors()`.
 */
class ContentPolicy
{
    use HandlesAuthorization;

    /**
     * Margen mínimo con el que un colaborador puede programar una publicación.
     */
    public const CONTRIBUTOR_SCHEDULE_DAYS = 7;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function view(User $user, Content $content): bool
    {
        return $this->reaches($user, $content);
    }

    /**
     * Crear: administración, o un Editor con alguna plataforma asignada.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isEditor() && $user->platforms()->exists());
    }

    /**
     * Crear en una plataforma concreta: administración, o esa plataforma
     * asignada.
     */
    public function createIn(User $user, ?int $platformId): bool
    {
        return $user->canManagePlatform($platformId);
    }

    public function update(User $user, Content $content): bool
    {
        return $this->reaches($user, $content);
    }

    public function delete(User $user, Content $content): bool
    {
        return $this->owns($user, $content);
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
     * Cambiar autor, plataforma y colaboradores.
     */
    public function manage(User $user, Content $content): bool
    {
        return $this->owns($user, $content);
    }

    /**
     * Publicar al momento.
     */
    public function publish(User $user, Content $content): bool
    {
        return $this->owns($user, $content);
    }

    /**
     * Programar la publicación para `$at`: administración o autor, cualquier
     * fecha futura; un colaborador, con al menos `CONTRIBUTOR_SCHEDULE_DAYS`
     * días de margen desde ahora.
     */
    public function schedule(User $user, Content $content, CarbonInterface $at): bool
    {
        if ($this->owns($user, $content)) {
            return $at->isFuture();
        }

        if ($this->reaches($user, $content)) {
            return $at->greaterThanOrEqualTo(now()->addDays(self::CONTRIBUTOR_SCHEDULE_DAYS));
        }

        return false;
    }

    /**
     * Administración, autoría o colaboración.
     */
    private function reaches(User $user, Content $content): bool
    {
        return $this->owns($user, $content)
            || ($user->isEditor() && $content->hasContributor($user));
    }

    /**
     * Administración o autoría: el control completo.
     */
    private function owns(User $user, Content $content): bool
    {
        return $user->isAdmin() || $this->isAuthor($user, $content);
    }

    /**
     * Autoría del contenido.
     *
     * La columna es `author_id`, no `user_id`. Aquí se leía `$content->user_id`,
     * que en `contents` no existe: siempre valía null, así que la comparación
     * con el id del usuario nunca se cumplía y un autor NO alcanzaba su propio
     * contenido. PHPStan ya lo señalaba; estaba silenciado en el baseline.
     */
    private function isAuthor(User $user, Content $content): bool
    {
        return $content->author_id !== null
            && (int) $content->author_id === (int) $user->id;
    }
}
