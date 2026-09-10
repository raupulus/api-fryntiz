<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `keycounter:fix_weekday` normaliza las rachas anteriores a 2020.
 *
 * Hasta diciembre de 2019 la columna `weekday` sigue la convención de Carbon
 * (0 = domingo); desde febrero de 2020, la del cliente (0 = lunes). El cliente
 * cambió y la plataforma no se enteró, así que las dos poblaciones conviven en
 * la misma columna.
 *
 * Esto reescribe datos históricos, así que lo que más importa aquí es lo que
 * el comando **no** toca.
 */
class KeyCounterFixWeekdayCommandTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->device = HardwareDevice::create(['name' => 'Thinkpad']);
    }

    /**
     * @param  string  $when  Fecha de la racha.
     * @param  int  $weekday  Valor tal cual está guardado.
     */
    private function streak(string $when, int $weekday): Keyboard
    {
        return Keyboard::create([
            'hardware_device_id' => $this->device->id,
            'start_at' => $when,
            'end_at' => $when,
            'duration' => 300,
            'pulsations' => 100,
            'pulsations_special_keys' => 5,
            'pulsation_average' => 2.0,
            'score' => 50,
            'weekday' => $weekday,
        ]);
    }

    #[Test]
    public function dry_run_writes_nothing(): void
    {
        // 2019-12-28 fue sábado: con 0=domingo se guardaba como 6.
        $streak = $this->streak('2019-12-28 14:00:00', 6);

        $this->artisan('keycounter:fix_weekday')
            ->expectsOutputToContain('Modo seco')
            ->assertSuccessful();

        $this->assertSame(6, (int) $streak->refresh()->weekday);
    }

    #[Test]
    public function with_write_normalizes_old_streaks(): void
    {
        // Sábado guardado como 6 (domingo en la convención nueva) → 5.
        $streak = $this->streak('2019-12-28 14:00:00', 6);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(5, (int) $streak->refresh()->weekday);
    }

    #[Test]
    public function it_does_not_touch_anything_after_the_convention_change(): void
    {
        // 2020-03-04 fue miércoles. Con 0=lunes es 2, que es lo correcto.
        $modern = $this->streak('2020-03-04 10:00:00', 2);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(2, (int) $modern->refresh()->weekday);
    }

    /**
     * Lo que de verdad no puede romper: una racha antigua que ya estaba en la
     * convención buena, o que no cuadra con ninguna de las dos porque cruzaba
     * la medianoche. Convertir a ciegas todo lo anterior a 2020 las estropearía.
     */
    #[Test]
    public function it_respects_streaks_that_do_not_follow_the_old_convention(): void
    {
        // 2019-12-28, sábado. Guardado como 5, que ya es lo correcto con
        // 0=lunes y no coincide con la convención vieja: no se toca.
        $alreadyCorrect = $this->streak('2019-12-28 14:00:00', 5);

        // Y una que no cuadra con ninguna de las dos: racha de madrugada cuyo
        // día local es el siguiente, así que el cliente grabó el lunes (0). El
        // dato del cliente manda.
        $midnight = $this->streak('2019-12-28 23:50:00', 0);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(5, (int) $alreadyCorrect->refresh()->weekday);
        $this->assertSame(0, (int) $midnight->refresh()->weekday);
    }

    #[Test]
    public function the_date_limit_can_be_moved(): void
    {
        $streak = $this->streak('2019-12-28 14:00:00', 6);

        // Con un límite anterior a la racha, queda fuera del alcance.
        $this->artisan('keycounter:fix_weekday --write --until=2015-01-01')
            ->assertSuccessful();

        $this->assertSame(6, (int) $streak->refresh()->weekday);
    }
}
