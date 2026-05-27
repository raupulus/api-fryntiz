<?php

namespace App\Enums;

enum HardwareTypeEnum: string
{
    case Laptop = 'laptop';
    case Desktop = 'desktop';
    case Server = 'server';
    case Raspberry = 'raspberry';
    case Arduino = 'arduino';
    case Esp32 = 'esp32';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Laptop => 'Portatil',
            self::Desktop => 'Escritorio',
            self::Server => 'Servidor',
            self::Raspberry => 'Raspberry Pi',
            self::Arduino => 'Arduino',
            self::Esp32 => 'ESP32',
            self::Other => 'Otro',
        };
    }
}
