<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado operativo en tiempo real de una impresora física.
 */
enum PrinterStatusEnum: string
{
    case Ready = 'ready';
    case Busy = 'busy';
    case OutOfPaper = 'out_of_paper';
    case CoverOpen = 'cover_open';
    case Offline = 'offline';
    case Error = 'error';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ready => 'Lista',
            self::Busy => 'Imprimiendo',
            self::OutOfPaper => 'Sin papel',
            self::CoverOpen => 'Tapa abierta',
            self::Offline => 'Desconectada',
            self::Error => 'Error',
        };
    }

    /**
     * Color Filament correspondiente al estado.
     */
    public function color(): string
    {
        return match ($this) {
            self::Ready => 'success',
            self::Busy => 'info',
            self::OutOfPaper, self::CoverOpen => 'warning',
            self::Offline => 'gray',
            self::Error => 'danger',
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
