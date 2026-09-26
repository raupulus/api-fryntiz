<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Un fichero que no se puede subir a un contenido, con el motivo dicho para
 * quien escribe («La imagen pesa 21,3 MB y el máximo para imágenes es 20 MB»).
 * El editor lo enseña tal cual.
 */
class ContentUploadException extends RuntimeException {}
