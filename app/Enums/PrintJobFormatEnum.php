<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Formato del contenido o carga útil de un trabajo de impresión.
 */
enum PrintJobFormatEnum: string
{
    case Text = 'text';
    case Escpos = 'escpos';
    case Markdown = 'markdown';
    case Json = 'json';
    case Gcode = 'gcode';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto plano (UTF-8)',
            self::Escpos => 'Comandos ESC/POS (Base64)',
            self::Markdown => 'Markdown',
            self::Json => 'JSON',
            self::Gcode => 'G-code (3D)',
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

    /**
     * Devuelve todos los valores como array de strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
