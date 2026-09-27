<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages\Concerns;

use Filament\Actions\Action;

/**
 * «Guardar cambios» también en la cabecera de las secciones con formulario
 * (E4 de la auditoría de contenidos): el de abajo queda fuera de la vista en
 * un formulario largo.
 */
trait SavesFromTheHeader
{
    protected function saveOnTopAction(): Action
    {
        return $this->getSaveFormAction()->formId('form');
    }
}
