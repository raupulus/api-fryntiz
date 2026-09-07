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
    public function el_cero_es_lunes_y_el_seis_domingo(): void
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
    public function se_calcula_con_el_dia_iso_y_no_con_day_of_week(): void
    {
        // 2026-09-07 es lunes.
        $lunes = Carbon::parse('2026-09-07');

        $this->assertSame(0, KeyCounterWeekdayEnum::deLaFecha($lunes)->value);
        $this->assertSame(KeyCounterWeekdayEnum::Lunes, KeyCounterWeekdayEnum::deLaFecha($lunes));

        // Y con la convención vieja habría dado 1, que es el fallo de origen.
        $this->assertNotSame($lunes->dayOfWeek, KeyCounterWeekdayEnum::deLaFecha($lunes)->value);

        $domingo = Carbon::parse('2026-09-13');

        $this->assertSame(6, KeyCounterWeekdayEnum::deLaFecha($domingo)->value);
    }

    #[Test]
    public function la_semana_entera_cuadra(): void
    {
        $esperado = [
            '2026-09-07' => 'Lunes',
            '2026-09-08' => 'Martes',
            '2026-09-09' => 'Miércoles',
            '2026-09-10' => 'Jueves',
            '2026-09-11' => 'Viernes',
            '2026-09-12' => 'Sábado',
            '2026-09-13' => 'Domingo',
        ];

        foreach ($esperado as $fecha => $dia) {
            $this->assertSame(
                $dia,
                KeyCounterWeekdayEnum::deLaFecha(Carbon::parse($fecha))->label(),
                "El {$fecha} debería ser {$dia}.",
            );
        }
    }

    /**
     * En una tabla de administración interesa más ver el dato raro que un
     * error: la etiqueta de un valor fuera de rango es el valor.
     */
    #[Test]
    public function un_valor_fuera_de_rango_se_ensena_tal_cual(): void
    {
        $this->assertSame('9', KeyCounterWeekdayEnum::etiquetaDe(9));
        $this->assertNull(KeyCounterWeekdayEnum::etiquetaDe(null));
        $this->assertSame('Jueves', KeyCounterWeekdayEnum::etiquetaDe('3'));
    }
}
