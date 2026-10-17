<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cuánto razona el modelo antes de escribir.
 *
 * Son los tres niveles que entienden todos los proveedores: `thinkingLevel`
 * en Gemini, `reasoning.effort` en la API Responses (OpenAI y xAI) y
 * `reasoning_effort` en Mistral. Sólo se manda si el proveedor lo tiene
 * marcado en el panel: a un modelo sin razonamiento le da error.
 *
 * Gemini 3.8 Flash razona siempre: no tiene nivel «apagado», y `minimal` da
 * error. Ver https://ai.google.dev/gemini-api/docs/generate-content/thinking.
 */
enum AiReasoningLevelEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Bajo (más rápido)',
            self::Medium => 'Medio (recomendado)',
            self::High => 'Alto (razona más, tarda más)',
        };
    }

    public static function default(): self
    {
        return self::Medium;
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
