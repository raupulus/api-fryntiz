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
     * Cifra grande redondeada a millares: `75884812` → «75.885».
     *
     * Las tarjetas de la portada de KeyCounter acumulan decenas de millones de
     * pulsaciones. Los tres últimos dígitos no dicen nada, cambian a cada rato
     * y hacen la tarjeta más larga, así que se recorta la escala.
     *
     * Por debajo del millar se devuelve la cifra tal cual: redondear 812
     * pulsaciones a millares deja un «0» en la tarjeta, que es peor que el
     * cambio de escala.
     */
    public static function abbreviated(int|float|string|null $value): string
    {
        $number = (float) $value;

        if (abs($number) < 1000) {
            return self::asInteger($number);
        }

        return self::asInteger($number / 1000);
    }
}
