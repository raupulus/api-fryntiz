<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Inactive => 'Inactiva',
            self::Maintenance => 'En mantenimiento',
        };
    }
}
