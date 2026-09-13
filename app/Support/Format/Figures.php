<?php

declare(strict_types=1);

namespace App\Support\Format;

/**
 * Formato de las cifras que se enseñan en las vistas públicas.
 *
 * Separador de millar español (punto) y sin decimales: los contadores de
 * pulsaciones y clicks son acumulados de millones, así que la precisión hasta
 * la unidad no aporta nada y sólo mete ruido.
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
}
