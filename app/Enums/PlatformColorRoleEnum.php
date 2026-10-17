<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Para qué usa la web de una plataforma cada color de su tema. Se le dice a la
 * IA al generar una imagen: sabiendo cuál es el fondo, cuida el contraste.
 */
enum PlatformColorRoleEnum: string
{
    case Background = 'background';
    case Text = 'text';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Accent = 'accent';

    public function label(): string
    {
        return match ($this) {
            self::Background => 'Fondo',
            self::Text => 'Texto',
            self::Primary => 'Principal',
            self::Secondary => 'Secundario',
            self::Accent => 'Acento',
        };
    }

    /**
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
