<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Content\ContentPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Las páginas siguen a la política de su contenido (DUDA-2): quien edita el
 * contenido añade, edita, reordena y elimina sus páginas.
 *
 * `viewAny` y `create` no reciben la página (el panel pregunta con la clase):
 * dicen si el rol trabaja con contenidos; el contenido concreto ya lo ha
 * comprobado la ficha, que pide `update` sobre él para abrirse.
 */
class ContentPagePolicy
{
    use HandlesAuthorization;

    public function __construct(private readonly ContentPolicy $contents) {}

    public function viewAny(User $user): bool
    {
        return $this->contents->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->contents->viewAny($user);
    }

    public function view(User $user, ContentPage $page): bool
    {
        return $this->reaches($user, $page);
    }

    public function update(User $user, ContentPage $page): bool
    {
        return $this->reaches($user, $page);
    }

    public function delete(User $user, ContentPage $page): bool
    {
        return $this->reaches($user, $page);
    }

    /**
     * Sacarla de la papelera: quien puede editar el contenido.
     */
    public function restore(User $user, ContentPage $page): bool
    {
        return $this->reaches($user, $page);
    }

    /**
     * Eliminarla definitivamente, como un contenido: sólo el SuperAdmin (G3).
     */
    public function forceDelete(User $user, ContentPage $page): bool
    {
        return $user->isSuperAdmin();
    }

    public function reorder(User $user): bool
    {
        return $this->contents->viewAny($user);
    }

    private function reaches(User $user, ContentPage $page): bool
    {
        // Sin carga perezosa: la tabla de páginas pregunta fila a fila.
        $content = $page->relationLoaded('contentModel') ? $page->contentModel : $page->contentModel()->first();

        return $content !== null && $this->contents->update($user, $content);
    }
}
