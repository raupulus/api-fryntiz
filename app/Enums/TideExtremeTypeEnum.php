<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipo de extremo de marea detectado sobre la serie horaria de Open-Meteo
 * Marine (`sea_level_height_msl`).
 */
enum TideExtremeTypeEnum: string
{
    case HighTide = 'Pleamar';
    case LowTide = 'Bajamar';

    public function label(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return match ($this) {
            self::HighTide => 'trending_up',
            self::LowTide => 'trending_down',
        };
    }
}
