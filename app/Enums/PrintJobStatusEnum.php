<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado del ciclo de vida de un trabajo en la cola de impresión.
 */
enum PrintJobStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Completed => 'Completado',
            self::Failed => 'Fallido',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Color Filament correspondiente al estado del trabajo.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'warning',
        };
    }

    /**
     * Devuelve las opciones como array value => label (para formularios y Filament).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
