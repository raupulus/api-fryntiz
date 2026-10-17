<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modos de operación del asistente IA en el editor de páginas.
 */
enum AiModeEnum: string
{
    case Create = 'create';
    case Edit = 'edit';
    case Correct = 'correct';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Crear nueva página',
            self::Edit => 'Editar / ampliar página actual',
            self::Correct => 'Solo corregir ortografía y optimizar estilo',
        };
    }

    /**
     * Opciones clave-valor para selectores de Filament.
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
