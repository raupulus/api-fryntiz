<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\KeyCounterWeekdayEnum;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `0` es domingo, la convención de `Carbon::dayOfWeek` (y la de JavaScript).
 */
class KeyCounterWeekdayEnumTest extends TestCase
{
    #[Test]
    public function zero_is_sunday_and_six_is_saturday(): void
    {
        $this->assertSame('Domingo', KeyCounterWeekdayEnum::from(0)->label());
        $this->assertSame('Sábado', KeyCounterWeekdayEnum::from(6)->label());

        $this->assertSame(
            ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            array_values(KeyCounterWeekdayEnum::options()),
        );
    }

    #[Test]
    public function it_is_calculated_with_carbon_day_of_week(): void
    {
        // 2026-09-07 es lunes.
        $monday = Carbon::parse('2026-09-07');

        $this->assertSame(1, KeyCounterWeekdayEnum::fromDate($monday)->value);
        $this->assertSame(KeyCounterWeekdayEnum::Monday, KeyCounterWeekdayEnum::fromDate($monday));
        $this->assertSame($monday->dayOfWeek, KeyCounterWeekdayEnum::fromDate($monday)->value);

        $sunday = Carbon::parse('2026-09-13');

        $this->assertSame(0, KeyCounterWeekdayEnum::fromDate($sunday)->value);
    }

    #[Test]
    public function the_whole_week_matches(): void
    {
        $expected = [
            '2026-09-06' => 'Domingo',
            '2026-09-07' => 'Lunes',
            '2026-09-08' => 'Martes',
            '2026-09-09' => 'Miércoles',
            '2026-09-10' => 'Jueves',
            '2026-09-11' => 'Viernes',
            '2026-09-12' => 'Sábado',
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
        $this->assertSame('Miércoles', KeyCounterWeekdayEnum::labelFor('3'));
    }
}
