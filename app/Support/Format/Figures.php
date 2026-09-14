<?php

declare(strict_types=1);

namespace App\Support\Format;

/**
 * Formato de las cifras que se enseñan en las vistas públicas.
 *
 * `asInteger()` y `abbreviated()` usan separador de millar español (punto) y
 * sin decimales: pensadas para acumulados de KeyCounter, que son millones de
 * pulsaciones donde la unidad no aporta nada. `rounded()` es para magnitudes
 * con decimales de verdad (vatios, voltios, kWh...), donde sí importan, pero
 * como máximo dos y sin forzar ceros que no miden nada.
 */
class Figures
{
    /**
     * Número entero con punto de millar: `1234.56` → «1.235».
     */
    public static function asInteger(int|float|string|null $value): string
    {
        return number_format(round((float) $value), 0, ',', '.');
    }

    /**
     * Cifra grande con sufijo de escala explícito: `75884812` → «75,88 M».
     *
     * Versión anterior (2026-09-06) dividía entre 1000 sin ningún sufijo:
     * `75884812` pulsaciones (75,9 millones) se mostraban como «75.885», una
     * cifra indistinguible de setenta y cinco mil. Nunca se debe recortar la
     * escala de un número sin dejar constancia de qué escala es.
     *
     * Por debajo del millón se devuelve la cifra íntegra (con separador de
     * millar): a esa escala no hace falta abreviar nada.
     */
    public static function abbreviated(int|float|string|null $value): string
    {
        $number = (float) $value;

        if (abs($number) < 1_000_000) {
            return self::asInteger($number);
        }

        return number_format($number / 1_000_000, 2, ',', '.').' M';
    }

    /**
     * Hasta N decimales, redondeados, sin forzar ceros que no miden nada:
     * `5.0` → «5», `5.256` → «5.26», `5.2` → «5.2».
     *
     * Antes las tarjetas de energía usaban `round()` (sin decimales, aunque
     * los hubiera) o `number_format($x, 1)` (siempre uno, aunque fuera «.0»)
     * según el sitio. Un único punto de redondeo para toda la magnitud
     * decimal de la página, y sin separador de millar: aquí no hace falta,
     * son vatios/voltios/kWh de una instalación, no pulsaciones acumuladas.
     */
    public static function rounded(int|float|string|null $value, int $decimals = 2): string
    {
        // Una cadena compuesta ("12.5 / 12.0 / 12.3") no es una cifra: un
        // `(float)` a secas se comería todo menos el primer número. Se
        // devuelve tal cual; quien la construya ya redondea cada parte antes
        // de unirlas.
        if (is_string($value) && ! is_numeric($value)) {
            return $value;
        }

        $rounded = number_format(round((float) $value, $decimals), $decimals, '.', '');

        return str_contains($rounded, '.')
            ? rtrim(rtrim($rounded, '0'), '.')
            : $rounded;
    }
}
