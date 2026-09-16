<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

use App\Enums\TideExtremeTypeEnum;
use Illuminate\Support\Carbon;

/**
 * Detecta pleamares y bajamares sobre la serie horaria `sea_level_height_msl`
 * de Open-Meteo Marine, por interpolación parabólica de 3 puntos.
 *
 * Es la misma interpolación de vértice de parábola que se usa para afinar
 * picos en FFT (Jacobsen/Candan): con 3 muestras equiespaciadas
 * `(h_prev, h, h_next)` en `x = -1, 0, 1`, el vértice de la parábola que pasa
 * por los tres cae en
 * `x* = (h_prev - h_next) / (2 · (h_prev - 2h + h_next))`
 * y su altura en `y* = h - (h_next - h_prev)² / (8 · (h_prev - 2h + h_next))`
 * — verificado a mano contra la fórmula de vértice de una parábola, y contra
 * una serie real de Open-Meteo el 2026-09-16.
 *
 * Esa verificación con datos reales encontró un caso que la detección ingenua
 * (sólo comparar cada punto con sus vecinos) no cubre: una meseta de dos horas
 * seguidas con la misma altura (-1.06 m a las 00:00 y la 01:00) se detecta
 * como DOS bajamares distintos, a una hora de diferencia, en vez de un único
 * mínimo real. Físicamente no puede haber dos pleamares o dos bajamares con
 * menos de ~6 h entre sí (semidiurna: dos ciclos completos al día), así que
 * {@see self::merge()} funde los del mismo tipo separados por menos de
 * `min_hours_between_same_type` quedándose con el más extremo.
 */
class TideExtremesCalculator
{
    /**
     * @param  array<int,string>  $times  ISO 8601 sin zona (formato de Open-Meteo), en pasos de 1 h
     * @param  array<int,float>  $heights  Metros MSL, mismo índice que `$times`
     * @return array<int,array{type:TideExtremeTypeEnum,happens_at:Carbon,height_m:float}> Ordenado por fecha
     */
    public static function extremes(array $times, array $heights, string $timezone, ?float $minHoursBetweenSameType = null): array
    {
        $count = count($heights);
        $raw = [];

        for ($i = 1; $i < $count - 1; $i++) {
            $hPrev = $heights[$i - 1];
            $h = $heights[$i];
            $hNext = $heights[$i + 1];

            $isHigh = $h >= $hPrev && $h >= $hNext && ($h > $hPrev || $h > $hNext);
            $isLow = $h <= $hPrev && $h <= $hNext && ($h < $hPrev || $h < $hNext);

            if (! $isHigh && ! $isLow) {
                continue;
            }

            $denom = $hPrev - 2 * $h + $hNext;
            $offset = $denom === 0.0 ? 0.0 : max(-1.0, min(1.0, 0.5 * ($hPrev - $hNext) / $denom));
            $heightPeak = $h - 0.25 * ($hPrev - $hNext) * $offset;

            $raw[] = [
                'type' => $isHigh ? TideExtremeTypeEnum::HighTide : TideExtremeTypeEnum::LowTide,
                'happens_at' => Carbon::parse($times[$i], $timezone)->addSeconds((int) round($offset * 3600)),
                'height_m' => round($heightPeak, 3),
            ];
        }

        return self::merge($raw, $minHoursBetweenSameType ?? (float) config('open_meteo_marine.min_hours_between_same_type', 3));
    }

    /**
     * Funde extremos consecutivos del mismo tipo separados por menos de
     * `$minHours`, quedándose con el más extremo (menor altura para Bajamar,
     * mayor para Pleamar).
     *
     * @param  array<int,array{type:TideExtremeTypeEnum,happens_at:Carbon,height_m:float}>  $extremes  Ya en orden cronológico
     * @return array<int,array{type:TideExtremeTypeEnum,happens_at:Carbon,height_m:float}>
     */
    private static function merge(array $extremes, float $minHours): array
    {
        $merged = [];

        foreach ($extremes as $extreme) {
            $last = end($merged);

            if ($last !== false
                && $last['type'] === $extreme['type']
                && $extreme['happens_at']->diffInMinutes($last['happens_at']) < $minHours * 60
            ) {
                $isMoreExtreme = $extreme['type'] === TideExtremeTypeEnum::HighTide
                    ? $extreme['height_m'] > $last['height_m']
                    : $extreme['height_m'] < $last['height_m'];

                if ($isMoreExtreme) {
                    $merged[array_key_last($merged)] = $extreme;
                }

                continue;
            }

            $merged[] = $extreme;
        }

        return $merged;
    }
}
