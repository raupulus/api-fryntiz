<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipos de tecnología de periféricos de impresión.
 */
enum PrinterTypeEnum: string
{
    case Thermal = 'thermal';
    case Ticket = 'ticket';
    case TwoD = '2d';
    case ThreeD = '3d';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Thermal => 'Térmica (Tickets/Etiquetas)',
            self::Ticket => 'Matricial / Impacto',
            self::TwoD => '2D (Tinta / Láser)',
            self::ThreeD => '3D',
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
