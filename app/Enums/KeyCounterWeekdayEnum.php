<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * El día de la semana de una racha de KeyCounter.
 *
 * **`0` es lunes**, que es lo que manda el cliente: `datetime.weekday()` de
 * Python. No es la convención de Carbon ni la de JavaScript, donde el 0 es el
 * domingo, y esa diferencia estaba repartida por todo el proyecto:
 *
 * | Sitio | Decía |
 * |---|---|
 * | `docs/info/keycounter.md` | 0 = lunes ✅ |
 * | `docs/info/api/v2/keycounter.md` | 0 = domingo ❌ |
 * | PHPDoc de `Keyboard` y `Mouse` | 0 = domingo ❌ |
 * | Filtros de los recursos de Filament | 0 = Dom ❌ |
 * | `SeedKeyCounterDebugCommand` | `Carbon::dayOfWeek`, 0 = domingo ❌ |
 *
 * Los `FormRequest` sólo validan `min:0|max:6`, así que la API acepta cualquier
 * número y nunca ha corregido nada: **los datos guardados son correctos**, lo
 * que estaba mal era la mitad de la plataforma que los leía.
 *
 * ⚠️ **Ojo con los datos anteriores a 2020.** Comprobado sobre 1,3 millones de
 * filas reales: hasta diciembre de 2019 la columna `weekday` sigue la
 * convención de Carbon (0 = domingo) al 100 %, y desde febrero de 2020 sigue
 * ésta (0 = lunes) en el 95 % de las filas —el resto son rachas que cruzan la
 * medianoche, donde `start_at` se guarda en UTC y el cliente calcula el día en
 * hora local. O sea que el cliente cambió de convención y la plataforma no se
 * enteró. `keycounter:fix_weekday` normaliza lo viejo; ver
 * `docs/info/keycounter.md`.
 */
enum KeyCounterWeekdayEnum: int
{
    case Monday = 0;
    case Tuesday = 1;
    case Wednesday = 2;
    case Thursday = 3;
    case Friday = 4;
    case Saturday = 5;
    case Sunday = 6;

    /**
     * Fecha a partir de la cual los datos usan esta convención.
     *
     * Antes de esto la columna `weekday` está en la convención de Carbon. Sirve
     * de límite al comando de normalización.
     */
    public const CONVENTION_CHANGE_DATE = '2020-01-01';

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Lunes',
            self::Tuesday => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday => 'Jueves',
            self::Friday => 'Viernes',
            self::Saturday => 'Sábado',
            self::Sunday => 'Domingo',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::Monday => 'Lun',
            self::Tuesday => 'Mar',
            self::Wednesday => 'Mié',
            self::Thursday => 'Jue',
            self::Friday => 'Vie',
            self::Saturday => 'Sáb',
            self::Sunday => 'Dom',
        };
    }

    /**
     * Etiqueta de un valor crudo de la columna, para pintar una tabla.
     *
     * Un valor fuera de rango se enseña tal cual en vez de reventar: en una
     * tabla de administración interesa más ver el dato raro que un error.
     */
    public static function labelFor(int|string|null $weekday): ?string
    {
        if ($weekday === null || $weekday === '') {
            return null;
        }

        return self::tryFrom((int) $weekday)?->label() ?? (string) $weekday;
    }

    /**
     * Opciones para un `Select` o un filtro, en orden de lunes a domingo.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $day) {
            $options[$day->value] = $day->label();
        }

        return $options;
    }

    /**
     * El día de la semana de una fecha, en esta convención.
     *
     * `dayOfWeekIso` va de 1 (lunes) a 7 (domingo), así que restar uno da
     * justo lo que manda el cliente. Usar `dayOfWeek` daría la convención
     * contraria, que es de donde viene todo el lío.
     */
    public static function fromDate(CarbonInterface $date): self
    {
        return self::from($date->dayOfWeekIso - 1);
    }
}
