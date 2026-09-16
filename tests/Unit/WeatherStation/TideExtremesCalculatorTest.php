<?php

declare(strict_types=1);

namespace Tests\Unit\WeatherStation;

use App\Enums\TideExtremeTypeEnum;
use App\Support\WeatherStation\TideExtremesCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La serie de `$heights` de este test es la respuesta real de Open-Meteo
 * Marine para Chipiona el 2026-09-16 (`sea_level_height_msl`, 48 h). Se deja
 * tal cual, no simplificada, porque fue verificándola a mano como se encontró
 * el caso de la meseta que {@see TideExtremesCalculator::merge()} corrige: sin
 * datos reales, ese caso no se habría visto.
 */
class TideExtremesCalculatorTest extends TestCase
{
    private const TIMES = [
        '2026-09-16T00:00', '2026-09-16T01:00', '2026-09-16T02:00', '2026-09-16T03:00',
        '2026-09-16T04:00', '2026-09-16T05:00', '2026-09-16T06:00', '2026-09-16T07:00',
        '2026-09-16T08:00', '2026-09-16T09:00', '2026-09-16T10:00', '2026-09-16T11:00',
        '2026-09-16T12:00', '2026-09-16T13:00', '2026-09-16T14:00', '2026-09-16T15:00',
        '2026-09-16T16:00', '2026-09-16T17:00', '2026-09-16T18:00', '2026-09-16T19:00',
        '2026-09-16T20:00', '2026-09-16T21:00', '2026-09-16T22:00', '2026-09-16T23:00',
        '2026-09-17T00:00', '2026-09-17T01:00', '2026-09-17T02:00', '2026-09-17T03:00',
        '2026-09-17T04:00', '2026-09-17T05:00', '2026-09-17T06:00', '2026-09-17T07:00',
        '2026-09-17T08:00', '2026-09-17T09:00', '2026-09-17T10:00', '2026-09-17T11:00',
        '2026-09-17T12:00', '2026-09-17T13:00', '2026-09-17T14:00', '2026-09-17T15:00',
        '2026-09-17T16:00', '2026-09-17T17:00', '2026-09-17T18:00', '2026-09-17T19:00',
        '2026-09-17T20:00', '2026-09-17T21:00', '2026-09-17T22:00', '2026-09-17T23:00',
    ];

    private const HEIGHTS = [
        -0.9, -0.62, -0.24, 0.16, 0.44, 0.61, 0.65, 0.58, 0.39, 0.1, -0.28, -0.72,
        -1.15, -1.29, -1.19, -0.85, -0.39, 0.09, 0.49, 0.53, 0.31, -0.06, -0.44, -0.84,
        -1.06, -1.06, -0.88, -0.57, -0.19, 0.16, 0.4, 0.5, 0.42, 0.18, -0.14, -0.5,
        -0.85, -1.06, -1.05, -0.85, -0.5, -0.06, 0.29, 0.3, 0.14, -0.16, -0.5, -0.79,
    ];

    #[Test]
    public function detects_alternating_semidiurnal_extremes(): void
    {
        $extremes = TideExtremesCalculator::extremes(self::TIMES, self::HEIGHTS, 'Europe/Madrid');

        $types = array_map(fn (array $e) => $e['type'], $extremes);

        // Semidiurna: alternan siempre, nunca dos Pleamares o dos Bajamares seguidos.
        for ($i = 1; $i < count($types); $i++) {
            $this->assertNotSame($types[$i - 1], $types[$i], "Dos extremos del mismo tipo seguidos en la posición {$i}");
        }
    }

    #[Test]
    public function merges_the_flat_plateau_into_a_single_low_tide(): void
    {
        // 00:00 y 01:00 valen igual (-1.06): la detección ingenua marca las
        // dos como Bajamar independientes, a 1h de diferencia — imposible
        // físicamente (periodo semidiurno ~6h). Debe quedar una sola, con la
        // altura más extrema de las dos candidatas.
        $extremes = TideExtremesCalculator::extremes(self::TIMES, self::HEIGHTS, 'Europe/Madrid');

        $aroundMidnight = array_values(array_filter(
            $extremes,
            fn (array $e) => $e['happens_at']->isBetween('2026-09-16 23:00:00', '2026-09-17 02:00:00')
        ));

        $this->assertCount(1, $aroundMidnight, 'Debe quedar un único extremo fundido, no dos');
        $this->assertSame(TideExtremeTypeEnum::LowTide, $aroundMidnight[0]['type']);
        $this->assertEqualsWithDelta(-1.088, $aroundMidnight[0]['height_m'], 0.01);
    }

    #[Test]
    public function returns_empty_when_the_series_is_too_short(): void
    {
        $this->assertSame([], TideExtremesCalculator::extremes(['2026-01-01T00:00'], [0.0], 'UTC'));
    }
}
