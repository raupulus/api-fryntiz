<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ubicación física de un dispositivo de hardware: interior o exterior.
 *
 * Aplica a cualquier hardware (por defecto interior). En estaciones
 * meteorológicas permite además no mezclar lecturas tan dispares como la
 * temperatura de un salón y la de la azotea en pleno verano.
 */
enum HardwareLocationTypeEnum: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Indoor => 'Interior',
            self::Outdoor => 'Exterior',
        };
    }

    /**
     * Devuelve las opciones como array value => label (para formularios/Filament).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Indoor->value => self::Indoor->label(),
            self::Outdoor->value => self::Outdoor->label(),
        ];
    }
}
