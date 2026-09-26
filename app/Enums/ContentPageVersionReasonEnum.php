<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Por qué una versión de una página pasó al historial
 * (`content_page_versions.reason`).
 *
 * La versión es siempre lo que había **antes** del cambio: el motivo dice qué
 * cambio la sustituyó.
 */
enum ContentPageVersionReasonEnum: string
{
    case Save = 'save';
    case FormatChange = 'format_change';
    case Restore = 'restore';
    case DraftRestore = 'draft_restore';
    case Emptied = 'emptied';

    public function label(): string
    {
        return match ($this) {
            self::Save => 'Guardado',
            self::FormatChange => 'Cambio de formato',
            self::Restore => 'Recuperación de una versión',
            self::DraftRestore => 'Recuperación de un borrador',
            self::Emptied => 'Página vaciada',
        };
    }
}
