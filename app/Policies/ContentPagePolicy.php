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

    public function reorder(User $user): bool
    {
        return $this->contents->viewAny($user);
    }

    private function reaches(User $user, ContentPage $page): bool
    {
        $content = $page->contentModel;

        return $content !== null && $this->contents->update($user, $content);
    }
}
