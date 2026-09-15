<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Gdacs\GdacsEvent;
use App\Models\User;

/**
 * Eventos de GDACS: los sincroniza `gdacs:sync` en solo lectura, nadie los
 * crea ni los edita a mano desde el panel — ni siquiera un administrador.
 * Editarlos manualmente desincroniza la copia local de lo que GDACS dice de
 * verdad, que es justo lo único que le da valor a la tabla.
 *
 * Sin dueño, como los catálogos de {@see AdminCatalogPolicy}, pero con una
 * diferencia real: aquel permite crear/editar/borrar a un administrador, esto
 * no permite ninguna de las tres a nadie. Por eso policy propia y no la
 * reutilización de aquella.
 */
class GdacsEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, GdacsEvent $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, GdacsEvent $model): bool
    {
        return false;
    }

    public function delete(User $user, GdacsEvent $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
