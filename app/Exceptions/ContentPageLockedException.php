<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * La página está bloqueada por otra persona, o por el mismo usuario en otra
 * pestaña, y no se puede guardar desde aquí (P4 de la auditoría de contenidos
 * del 2026-09-24). El mensaje dice quién la tiene.
 */
class ContentPageLockedException extends RuntimeException {}
