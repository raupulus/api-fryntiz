<?php

declare(strict_types=1);

namespace App\Support\KeyCounter;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Las claves de caché de KeyCounter, en un sitio.
 *
 * Estaban repartidas como cadenas sueltas entre el controlador y el servicio,
 * que es la forma clásica de que una invalidación deje de coincidir con lo que
 * guarda y nadie se entere: la página sigue funcionando, sólo que enseña datos
 * viejos.
 *
 * **El almacén es `file`** (`CACHE_STORE=file`), que **no soporta etiquetas**,
 * así que no hay `Cache::tags()`: todo se invalida por clave explícita.
 *
 * ## Por qué dos ventanas
 *
 * Un mes cerrado no va a recibir rachas nuevas nunca más, así que se guarda
 * para siempre. El mes en curso se recalcula cada cuarto de hora, que además
 * cubre lo que pedía el usuario: que la web no refleje la actividad en tiempo
 * real.
 *
 * El mes **recién cerrado** es el caso delicado: si alguien visitó la página el
 * día 31 a las once de la noche, guardar eso «para siempre» congelaría el mes
 * con su último día a medias. Y una racha puede llegar tarde: un cacharro que
 * estuvo sin red sube lo acumulado cuando la recupera.
 *
 * Por eso `esPeriodoCerrado()` deja fuera también el **mes anterior**, que sigue
 * con ventana corta mientras dure el mes en curso. Sale caro recalcularlo cada
 * cuarto de hora durante un mes, pero es un mes de trabajo de más frente a un
 * histórico congelado mal, que no se arregla solo y nadie mira.
 */
class KeyCounterCache
{
    /** Un cuarto de hora: lo que tarda la web en reflejar la actividad. */
    public const VENTANA_CORTA = 900;

    /**
     * Datos de la gráfica de un mes concreto.
     *
     * La clave lleva año **y** mes: con sólo uno de los dos, navegar entre
     * meses enseñaría los datos de otro, que es el fallo más fácil de cometer
     * aquí y el más difícil de ver.
     */
    public static function claveGrafica(int $year, int $month): string
    {
        return "keycounter:graph:{$year}-{$month}";
    }

    /**
     * ¿El mes pedido está cerrado del todo?
     *
     * «Cerrado» quiere decir que ya no va a recibir rachas nuevas: ni el mes en
     * curso ni el anterior, donde todavía puede caer lo que un cacharro tenía
     * sin subir. Del anteanterior hacia atrás, a la caja fuerte.
     */
    public static function esPeriodoCerrado(int $year, int $month): bool
    {
        $ahora = now();
        $limite = $ahora->copy()->startOfMonth()->subDay();

        return $year < $limite->year
            || ($year === $limite->year && $month < $limite->month);
    }

    /**
     * Guarda el resultado con la ventana que le toque al periodo.
     *
     * @template T
     *
     * @param  Closure(): T  $calcular
     * @return T
     */
    public static function recordarGrafica(int $year, int $month, Closure $calcular): mixed
    {
        $clave = self::claveGrafica($year, $month);

        return self::esPeriodoCerrado($year, $month)
            ? Cache::rememberForever($clave, $calcular)
            : Cache::remember($clave, self::VENTANA_CORTA, $calcular);
    }

    /**
     * Olvida lo que deja de valer cuando llega una racha nueva.
     *
     * El mes en curso y el anterior: una racha puede llegar con fecha de ayer
     * —un cacharro que estuvo sin red y sube lo acumulado— y el día 1 eso cae
     * en el mes pasado.
     */
    public static function olvidarLoAfectadoPorUnaRachaNueva(): void
    {
        $ahora = now();

        foreach ([$ahora, $ahora->copy()->subMonthNoOverflow()] as $momento) {
            Cache::forget(self::claveGrafica($momento->year, $momento->month));
        }
    }

    /**
     * Olvida la gráfica de un mes concreto.
     *
     * Para después de un `keycounter:remove_duplicate` o de un
     * `keycounter:fix_weekday`, que sí tocan meses ya cerrados.
     */
    public static function olvidarGrafica(int $year, int $month): void
    {
        Cache::forget(self::claveGrafica($year, $month));
    }
}
