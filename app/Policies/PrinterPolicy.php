<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Printer;
use App\Models\PrinterStack;
use Illuminate\Database\Eloquent\Model;

/**
 * Impresoras y su cola de impresión.
 *
 * Un trabajo en cola se hereda de la impresora: quien no alcanza el aparato no
 * alcanza lo que se imprime en él.
 */
class PrinterPolicy extends OwnedResourcePolicy
{
    protected function ownerId(Model $model): ?int
    {
        if ($model instanceof PrinterStack) {
            $model = $model->printer;
        }

        if (! $model instanceof Printer) {
            return null;
        }

        return $model->user_id === null ? null : (int) $model->user_id;
    }
}
