<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `keycounter:fix_weekday` recalcula `weekday` desde `start_at`: `0` es
 * siempre domingo (`Carbon::dayOfWeek`), sin distinguir épocas ni fecha de
 * corte — cualquier fila que no cuadre con el día real se corrige.
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
        // 2026-09-07 es lunes (1), guardado como si fuera domingo/lunes de
        // la convención vieja (0).
        $streak = $this->streak('2026-09-07 14:00:00', 0);

        $this->artisan('keycounter:fix_weekday')
            ->expectsOutputToContain('Modo seco')
            ->assertSuccessful();

        $this->assertSame(0, (int) $streak->refresh()->weekday);
    }

    #[Test]
    public function with_write_recalculates_mismatched_streaks(): void
    {
        // 2026-09-07 es lunes → 1, no 0.
        $streak = $this->streak('2026-09-07 14:00:00', 0);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(1, (int) $streak->refresh()->weekday);
    }

    #[Test]
    public function it_does_not_touch_streaks_that_already_match(): void
    {
        // 2026-09-06 es domingo → 0, que ya es correcto.
        $streak = $this->streak('2026-09-06 10:00:00', 0);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(0, (int) $streak->refresh()->weekday);
    }

    /**
     * No hay fecha de corte: una racha de 2019 con el valor equivocado se
     * corrige igual que una reciente.
     */
    #[Test]
    public function it_fixes_old_streaks_too_with_no_cutoff_date(): void
    {
        // 2019-12-28 fue sábado → 6, guardado como 1 (equivocado).
        $streak = $this->streak('2019-12-28 14:00:00', 1);

        $this->artisan('keycounter:fix_weekday --write')->assertSuccessful();

        $this->assertSame(6, (int) $streak->refresh()->weekday);
    }
}
