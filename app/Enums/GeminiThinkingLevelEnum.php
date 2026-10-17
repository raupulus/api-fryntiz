<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cuánto razona Gemini antes de escribir (`thinkingLevel` de la API).
 *
 * Gemini 3.8 Flash razona siempre: no tiene nivel «apagado», y `minimal` da
 * error. Los presupuestos en tokens (`thinkingBudget`) son de los modelos
 * anteriores. Ver https://ai.google.dev/gemini-api/docs/generate-content/thinking.
 */
enum GeminiThinkingLevelEnum: string
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

    /**
     * El nivel de `.env` (`GEMINI_THINKING_LEVEL`), o el medio si no vale.
     */
    public static function default(): self
    {
        return self::tryFrom((string) config('google.gemini.thinking_level')) ?? self::Medium;
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
