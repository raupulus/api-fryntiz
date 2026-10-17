<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modelos de Google Gemini que usa el asistente de contenidos.
 *
 * Sólo los que existen de verdad en la API para generar texto. El 2.0 Flash
 * lo apagó Google el 2026-06-01, y los 2.5 sólo se sirven a los proyectos que
 * ya los usaban. Ver https://ai.google.dev/gemini-api/docs/models.
 */
enum GeminiModelEnum: string
{
    case Gemini38Flash = 'gemini-3.8-flash';

    public function label(): string
    {
        return match ($this) {
            self::Gemini38Flash => 'Gemini 3.8 Flash',
        };
    }

    /**
     * El modelo con el que trabaja el asistente.
     */
    public static function default(): self
    {
        return self::Gemini38Flash;
    }
}
