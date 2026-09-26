<?php

declare(strict_types=1);

namespace App\Services\Content;

/**
 * Resultado de convertir el contenido de una página a otro formato.
 */
final readonly class ContentConversion
{
    /**
     * @param  string  $content  Contenido ya convertido.
     * @param  list<string>  $warnings  Lo que se pierde o cambia, en español y para quien edita.
     * @param  string|null  $summary  Resumen del resultado (p. ej. bloques de Editor.js generados).
     */
    public function __construct(
        public string $content,
        public array $warnings = [],
        public ?string $summary = null,
    ) {}
}
