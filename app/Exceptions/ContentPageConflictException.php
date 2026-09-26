<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * La página se ha guardado desde otro sitio después de abrirla aquí (D4 de la
 * auditoría de contenidos del 2026-09-24): no se guarda, para no pisar lo
 * otro. Lo escrito sigue en el borrador de quien guardaba.
 */
class ContentPageConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta página se ha modificado mientras la editabas. Tu versión está a salvo en el borrador; recarga para ver la otra.');
    }
}
