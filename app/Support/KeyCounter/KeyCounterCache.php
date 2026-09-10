<?php

declare(strict_types=1);

namespace App\Support\KeyCounter;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Las claves y las ventanas de caché de KeyCounter, en un sitio.
 *
 * Estaban repartidas como cadenas sueltas entre el controlador y el servicio,
 * que es la forma clásica de que una invalidación deje de coincidir con lo que
 * guarda y nadie se entere: la página sigue funcionando, sólo que enseña datos
 * viejos.
 *
 * **El almacén es `file`** (`CACHE_STORE=file`), que **no soporta etiquetas**,
 * así que no hay `Cache::tags()`: todo se invalida por clave explícita.
 *
 * ## Las dos ventanas, y por qué son dos
 *
 * Un mes cerrado no va a recibir rachas nuevas nunca más, así que se guarda
 * para siempre. Lo vivo —el mes en curso, el anterior, los resúmenes, los
 * widgets y el total del año en curso— tiene **una hora** de antigüedad
 * máxima: `FRESH_WINDOW`.
 *
 * Esa hora es una decisión de **privacidad**, no de rendimiento: la web no
 * refleja la actividad en tiempo real. Quien mire la página no sabe si se está
 * tecleando ahora mismo.
 *
 * Lo que hace que esa hora se cumpla de verdad es que **nadie invalida al
 * recibir una racha**. Hasta la revisión del 2026-09-10, `storeKeyboard()`
 * borraba estas claves en cada ingesta: la siguiente visita recalculaba con lo
 * recién llegado y la ventana no existía en la práctica —era tiempo real con
 * otro nombre— además de hacer que el visitante pagara el cálculo.
 *
 * ## Por qué el TTL guardado es el doble
 *
 * `keycounter:warm_cache --live` reescribe lo vivo cada hora (ver
 * `routes/console.php`). Si el TTL guardado fuera también de una hora, entre
 * que una entrada caduca y el cron la vuelve a escribir habría un hueco, y ese
 * hueco lo paga el primer visitante con el cálculo entero.
 *
 * Por eso se guarda con `STORAGE_WINDOW`, el doble: en marcha normal la entrada
 * se sobrescribe mucho antes de caducar, y si un pase del cron se salta o se
 * retrasa, la web sigue sirviendo lo anterior —datos de hasta dos horas, que
 * para la privacidad no es un problema— en vez de frenarse.
 *
 * ## Qué se considera un mes «cerrado»
 *
 * Ni el mes en curso ni el anterior. El mes **recién cerrado** es el caso
 * delicado: si alguien visitara la página el día 31 a las once de la noche,
 * guardar eso «para siempre» congelaría el mes con su último día a medias. Y
 * una racha puede llegar tarde: un cacharro que estuvo sin red sube lo
 * acumulado cuando la recupera.
 *
 * Por eso `isPeriodClosed()` deja fuera también el mes anterior, que sigue con
 * ventana corta mientras dure el mes en curso. Del anteanterior hacia atrás, a
 * la caja fuerte.
 */
class KeyCounterCache
{
    /**
     * Antigüedad máxima de lo que ve la web: una hora.
     *
     * Es a la vez el retardo de privacidad y la cadencia a la que
     * `keycounter:warm_cache --live` reescribe estas claves.
     */
    public const FRESH_WINDOW = 3600;

    /**
     * TTL con el que se guarda de verdad: el doble de `FRESH_WINDOW`.
     *
     * Margen para que un pase del refresco que se salte o se retrase no deje
     * la caché vacía y el cálculo se lo coma un visitante.
     */
    public const STORAGE_WINDOW = self::FRESH_WINDOW * 2;

    /** Resumen de las últimas rachas de teclado. */
    public const KEYBOARD_SUMMARY_KEY = 'keycounter:keyboard:summary';

    /** Resumen de las últimas rachas de ratón. */
    public const MOUSE_SUMMARY_KEY = 'keycounter:mouse:summary';

    /** Widgets: total global, mejor año, mejor mes, mejor día, mejor hora. */
    public const WIDGETS_KEY = 'keycounter:widgets';

    /**
     * Datos de la gráfica de un mes concreto.
     *
     * La clave lleva año **y** mes: con sólo uno de los dos, navegar entre
     * meses enseñaría los datos de otro, que es el fallo más fácil de cometer
     * aquí y el más difícil de ver.
     */
    public static function graphCacheKey(int $year, int $month): string
    {
        return "keycounter:graph:{$year}-{$month}";
    }

    /**
     * Total de pulsaciones de un año.
     */
    public static function yearTotalCacheKey(int $year): string
    {
        return "keycounter:year_total:{$year}";
    }

    /**
     * ¿El mes pedido está cerrado del todo?
     *
     * «Cerrado» quiere decir que ya no va a recibir rachas nuevas: ni el mes en
     * curso ni el anterior, donde todavía puede caer lo que un cacharro tenía
     * sin subir.
     */
    public static function isPeriodClosed(int $year, int $month): bool
    {
        $now = now();
        $limit = $now->copy()->startOfMonth()->subDay();

        return $year < $limit->year
            || ($year === $limit->year && $month < $limit->month);
    }

    /**
     * Los periodos abiertos: el mes en curso y el anterior.
     *
     * Es la lista que refresca `keycounter:warm_cache --live` y la que hay que
     * olvidar cuando algo los invalida de golpe.
     *
     * @return list<array{int, int}>
     */
    public static function openPeriods(): array
    {
        $now = now();
        $previous = $now->copy()->subMonthNoOverflow();

        return [
            [$now->year, $now->month],
            [$previous->year, $previous->month],
        ];
    }

    /**
     * Lee la gráfica de un mes, calculándola sólo si no está.
     *
     * Es la puerta de la **web**: nunca escribe nada que no haga falta. El
     * camino normal es que la entrada ya esté puesta por el refresco horario o,
     * si el mes está cerrado, por `keycounter:warm_cache`.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function rememberGraph(int $year, int $month, Closure $callback): mixed
    {
        $key = self::graphCacheKey($year, $month);

        return self::isPeriodClosed($year, $month)
            ? Cache::rememberForever($key, $callback)
            : Cache::remember($key, self::STORAGE_WINDOW, $callback);
    }

    /**
     * Escribe la gráfica de un mes esté o no esté ya.
     *
     * Es la puerta del **refresco programado**, que no quiere «si no está,
     * calcúlala» sino «recalcúlala». Con `remember()` un mes vivo no se
     * actualizaría nunca mientras su entrada siguiera viva.
     */
    public static function putGraph(int $year, int $month, mixed $value): void
    {
        $key = self::graphCacheKey($year, $month);

        if (self::isPeriodClosed($year, $month)) {
            Cache::forever($key, $value);

            return;
        }

        Cache::put($key, $value, self::STORAGE_WINDOW);
    }

    /**
     * Lee un agregado vivo (resúmenes, widgets, total del año en curso).
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function rememberLive(string $key, Closure $callback): mixed
    {
        return Cache::remember($key, self::STORAGE_WINDOW, $callback);
    }

    /**
     * Escribe un agregado vivo esté o no esté ya. Para el refresco programado.
     */
    public static function putLive(string $key, mixed $value): void
    {
        Cache::put($key, $value, self::STORAGE_WINDOW);
    }

    /**
     * Lee el total de un año: para siempre si está cerrado, vivo si es el actual.
     *
     * @param  Closure(): int  $callback
     */
    public static function rememberYearTotal(int $year, int $currentYear, Closure $callback): int
    {
        $key = self::yearTotalCacheKey($year);

        return $year < $currentYear
            ? (int) Cache::rememberForever($key, $callback)
            : (int) self::rememberLive($key, $callback);
    }

    /**
     * Olvida la gráfica de un mes concreto.
     */
    public static function forgetGraph(int $year, int $month): void
    {
        Cache::forget(self::graphCacheKey($year, $month));
    }

    /**
     * Olvida el total de un año concreto.
     */
    public static function forgetYearTotal(int $year): void
    {
        Cache::forget(self::yearTotalCacheKey($year));
    }

    /**
     * Olvida las gráficas del mes en curso y del anterior.
     *
     * No lo llama la ingesta —ver la explicación de arriba—, sino quien
     * necesita tirar lo vivo a mano.
     */
    public static function forgetOpenPeriods(): void
    {
        foreach (self::openPeriods() as [$year, $month]) {
            self::forgetGraph($year, $month);
        }
    }

    /**
     * Olvida todo lo que deja de valer cuando se tocan rachas ya guardadas.
     *
     * Para después de un `keycounter:remove_duplicate` o de un
     * `keycounter:fix_weekday`, que sí mueven meses **cerrados** —y esos están
     * guardados para siempre, así que sin esto la gráfica mala se queda puesta
     * hasta que alguien vacíe la caché entera a mano.
     *
     * Los widgets van siempre: cualquier borrado cambia el total global.
     *
     * @param  iterable<array{int, int}>  $periods  Pares [año, mes].
     */
    public static function forgetPeriods(iterable $periods): void
    {
        $years = [];

        foreach ($periods as [$year, $month]) {
            self::forgetGraph($year, $month);
            $years[$year] = true;
        }

        foreach (array_keys($years) as $year) {
            self::forgetYearTotal($year);
        }

        Cache::forget(self::WIDGETS_KEY);
    }
}
