<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\KeyCounterWeekdayEnum;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `0` es lunes, que es lo que manda el cliente (`datetime.weekday()` de
 * Python). La convención de Carbon y la de JavaScript ponen el domingo en el
 * 0, y esa diferencia estaba repartida por medio proyecto.
 */
class KeyCounterWeekdayEnumTest extends TestCase
{
    #[Test]
    public function zero_is_monday_and_six_is_sunday(): void
    {
        $this->assertSame('Lunes', KeyCounterWeekdayEnum::from(0)->label());
        $this->assertSame('Domingo', KeyCounterWeekdayEnum::from(6)->label());

        $this->assertSame(
            ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            array_values(KeyCounterWeekdayEnum::options()),
        );
    }

    /**
     * El error que había que evitar: `Carbon::dayOfWeek` da 0 = domingo, y con
     * él se sembraban los datos de depuración.
     */
    #[Test]
    public function it_is_calculated_with_the_iso_day_and_not_with_day_of_week(): void
    {
        // 2026-09-07 es lunes.
        $monday = Carbon::parse('2026-09-07');

        $this->assertSame(0, KeyCounterWeekdayEnum::fromDate($monday)->value);
        $this->assertSame(KeyCounterWeekdayEnum::Monday, KeyCounterWeekdayEnum::fromDate($monday));

        // Y con la convención vieja habría dado 1, que es el fallo de origen.
        $this->assertNotSame($monday->dayOfWeek, KeyCounterWeekdayEnum::fromDate($monday)->value);

        $sunday = Carbon::parse('2026-09-13');

        $this->assertSame(6, KeyCounterWeekdayEnum::fromDate($sunday)->value);
    }

    #[Test]
    public function the_whole_week_matches(): void
    {
        $expected = [
            '2026-09-07' => 'Lunes',
            '2026-09-08' => 'Martes',
            '2026-09-09' => 'Miércoles',
            '2026-09-10' => 'Jueves',
            '2026-09-11' => 'Viernes',
            '2026-09-12' => 'Sábado',
            '2026-09-13' => 'Domingo',
        ];

        foreach ($expected as $date => $day) {
            $this->assertSame(
                $day,
                KeyCounterWeekdayEnum::fromDate(Carbon::parse($date))->label(),
                "El {$date} debería ser {$day}.",
            );
        }
    }

    /**
     * En una tabla de administración interesa más ver el dato raro que un
     * error: la etiqueta de un valor fuera de rango es el valor.
     */
    #[Test]
    public function an_out_of_range_value_is_shown_as_is(): void
    {
        $this->assertSame('9', KeyCounterWeekdayEnum::labelFor(9));
        $this->assertNull(KeyCounterWeekdayEnum::labelFor(null));
        $this->assertSame('Jueves', KeyCounterWeekdayEnum::labelFor('3'));
    }
}
