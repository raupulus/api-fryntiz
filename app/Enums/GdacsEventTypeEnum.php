<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipos de desastre del sistema GDACS. El valor es el código corto de dos
 * letras que usa la propia API (`eventtype`), así el cast del modelo no
 * necesita traducir nada entre lo que llega y lo que se guarda.
 */
enum GdacsEventTypeEnum: string
{
    case Earthquake = 'EQ';
    case TropicalCyclone = 'TC';
    case Flood = 'FL';
    case Volcano = 'VO';
    case Drought = 'DR';
    case Wildfire = 'WF';

    public function label(): string
    {
        return match ($this) {
            self::Earthquake => 'Terremoto',
            self::TropicalCyclone => 'Ciclón tropical',
            self::Flood => 'Inundación',
            self::Volcano => 'Volcán',
            self::Drought => 'Sequía',
            self::Wildfire => 'Incendio forestal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Earthquake => 'heroicon-o-globe-europe-africa',
            self::TropicalCyclone => 'heroicon-o-cloud',
            self::Flood => 'heroicon-o-cloud-arrow-down',
            self::Volcano => 'heroicon-o-fire',
            self::Drought => 'heroicon-o-sun',
            self::Wildfire => 'heroicon-o-fire',
        };
    }
}
