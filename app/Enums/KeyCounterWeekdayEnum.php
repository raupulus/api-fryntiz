<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * El día de la semana de una racha de KeyCounter.
 *
 * **`0` es domingo**, la convención de `Carbon::dayOfWeek` (y la de
 * JavaScript). Una versión anterior de este enum asumía que el cliente subía
 * los datos con `datetime.weekday()` de Python (0 = lunes) y llegó a
 * normalizar datos históricos en esa dirección — era la convención
 * equivocada. `keycounter:fix_weekday` recalcula ahora `weekday` desde
 * `start_at` con esta convención para cualquier fila que no cuadre, sea de
 * cuando sea. Ver `docs/info/keycounter.md`.
 */
enum KeyCounterWeekdayEnum: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Domingo',
            self::Monday => 'Lunes',
            self::Tuesday => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday => 'Jueves',
            self::Friday => 'Viernes',
            self::Saturday => 'Sábado',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::Sunday => 'Dom',
            self::Monday => 'Lun',
            self::Tuesday => 'Mar',
            self::Wednesday => 'Mié',
            self::Thursday => 'Jue',
            self::Friday => 'Vie',
            self::Saturday => 'Sáb',
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
     * Opciones para un `Select` o un filtro.
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
     * El día de la semana de una fecha, en esta convención: la nativa de
     * Carbon, **0 = domingo**.
     */
    public static function fromDate(CarbonInterface $date): self
    {
        return self::from($date->dayOfWeek);
    }
}
