<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

use Illuminate\Support\Carbon;

/**
 * Fase lunar para una fecha dada, por fórmula astronómica (mes sinódico medio),
 * sin depender de ninguna API externa.
 *
 * Referencia: luna nueva conocida el 2000-01-06 18:14 UTC, mes sinódico medio
 * 29.53058868 días. Es una aproximación (varía ±unas horas por la excentricidad
 * orbital), suficiente para un badge informativo, no para navegación ni mareas.
 */
class MoonPhase
{
    private const SYNODIC_MONTH_DAYS = 29.53058868;

    private const KNOWN_NEW_MOON = '2000-01-06 18:14:00';

    /**
     * @return array{phase: string, emoji: string, illumination: int, age_days: float}
     */
    public static function forDate(Carbon $date): array
    {
        $knownNewMoon = Carbon::createFromFormat('Y-m-d H:i:s', self::KNOWN_NEW_MOON, 'UTC');

        $daysSinceKnownNewMoon = ($date->copy()->utc()->getTimestamp() - $knownNewMoon->getTimestamp()) / 86400;

        $age = fmod($daysSinceKnownNewMoon, self::SYNODIC_MONTH_DAYS);

        if ($age < 0) {
            $age += self::SYNODIC_MONTH_DAYS;
        }

        $illumination = (int) round((1 - cos(2 * M_PI * $age / self::SYNODIC_MONTH_DAYS)) / 2 * 100);

        [$phase, $emoji] = self::phaseForAge($age);

        return [
            'phase' => $phase,
            'emoji' => $emoji,
            'illumination' => $illumination,
            'age_days' => round($age, 1),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function phaseForAge(float $age): array
    {
        $segment = self::SYNODIC_MONTH_DAYS / 8;

        return match (true) {
            $age < $segment * 0.5, $age >= $segment * 7.5 => ['Luna nueva', '🌑'],
            $age < $segment * 1.5 => ['Creciente', '🌒'],
            $age < $segment * 2.5 => ['Cuarto creciente', '🌓'],
            $age < $segment * 3.5 => ['Gibosa creciente', '🌔'],
            $age < $segment * 4.5 => ['Luna llena', '🌕'],
            $age < $segment * 5.5 => ['Gibosa menguante', '🌖'],
            $age < $segment * 6.5 => ['Cuarto menguante', '🌗'],
            default => ['Menguante', '🌘'],
        };
    }
}
