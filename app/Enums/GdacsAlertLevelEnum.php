<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Nivel de alerta de un evento GDACS. El valor coincide con lo que devuelve
 * la API (`alertlevel`: "Green"/"Orange"/"Red"), con mayúscula inicial.
 */
enum GdacsAlertLevelEnum: string
{
    case Green = 'Green';
    case Orange = 'Orange';
    case Red = 'Red';

    public function label(): string
    {
        return match ($this) {
            self::Green => 'Verde',
            self::Orange => 'Naranja',
            self::Red => 'Rojo',
        };
    }

    /**
     * Color de Filament para badges/iconos.
     */
    public function color(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Orange => 'warning',
            self::Red => 'danger',
        };
    }
}
