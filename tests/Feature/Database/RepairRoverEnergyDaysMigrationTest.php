<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `2026_09_14_000003_repair_rover_energy_days`: reparación de los días del
 * Renogy Rover guardados mal entre el 7 y el 14 de septiembre de 2026.
 *
 * Se monta en pequeño lo que había en producción: los ids 4, 7 y 11 del Rover,
 * días con el contador del aparato sustituido, lecturas del sistema viejo sin
 * energía y lecturas nuevas con los Ah del panel y los Wh de la batería mal.
 */
class RepairRoverEnergyDaysMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function ejecutaLaMigracion(): void
    {
        (require database_path('migrations/2026_09_14_000003_repair_rover_energy_days.php'))->up();
    }

    private function montaElRover(): void
    {
        $ahora = now();

        DB::table('hardware_devices')->insert(['id' => 6, 'name' => 'Renogy Rover', 'created_at' => $ahora, 'updated_at' => $ahora]);

        foreach ([[4, 'generator', 24.0], [7, 'load', 12.0], [11, 'battery', 12.0]] as [$id, $papel, $tension]) {
            DB::table('hardware_energy')->insert([
                'id' => $id,
                'hardware_device_id' => 6,
                'hardware_device_monitorized_id' => 6,
                'role' => $papel,
                'sensor_position' => 0,
                'nominal_voltage' => $tension,
                'is_active' => true,
                'auto_calculate_history' => false,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function lectura(int $elemento, string $cuando, array $datos): void
    {
        DB::table('hardware_energy_readings')->insert($datos + [
            'hardware_device_id' => 6,
            'hardware_energy_id' => $elemento,
            'created_at' => $cuando,
            'updated_at' => $cuando,
        ]);
    }

    private function dia(int $elemento, string $fecha, float $wh, float $ah): void
    {
        DB::table('hardware_energy_today')->insert([
            'hardware_device_id' => 6,
            'hardware_energy_id' => $elemento,
            'date' => $fecha,
            'readings_count' => 1,
            'energy_wh' => $wh,
            'energy_ah' => $ah,
            'energy_wh_source' => 'device',
            'energy_ah_source' => 'device',
        ]);
    }

    private function sesion(int $elemento, float $wh, float $ah): void
    {
        DB::table('hardware_energy_historical')->insert([
            'hardware_device_id' => 6,
            'hardware_energy_id' => $elemento,
            'session_index' => 1,
            'energy_wh' => $wh,
            'energy_ah' => $ah,
            'energy_wh_source' => 'device',
            'energy_ah_source' => 'device',
        ]);
    }

    /**
     * @return array{wh: float, ah: float, ws: string, as: string}
     */
    private function valorDelDia(int $elemento, string $fecha): array
    {
        $fila = DB::table('hardware_energy_today')->where('hardware_energy_id', $elemento)->where('date', $fecha)->first();

        return ['wh' => (float) $fila->energy_wh, 'ah' => (float) $fila->energy_ah, 'ws' => $fila->energy_wh_source, 'as' => $fila->energy_ah_source];
    }

    private function montaLosDatosRotos(): void
    {
        $this->montaElRover();

        // Consumo: un día viejo bien (el 5) y uno con el contador de 19:14 (el 8).
        $this->dia(7, '2026-09-05', 999.0, 99.0);
        $this->dia(7, '2026-09-08', 120.0, 10.0);
        $this->lectura(7, '2026-09-08 10:00:00', ['power' => 24.0, 'amperage' => 2.0]);
        $this->lectura(7, '2026-09-08 10:05:00', ['power' => 24.0, 'amperage' => 2.0]);
        // Un hueco de dos horas cuenta como mucho 15 minutos.
        $this->lectura(7, '2026-09-08 12:05:00', ['power' => 24.0, 'amperage' => 2.0]);
        $this->dia(7, '2026-09-14', 447.0, 36.0);
        $this->lectura(7, '2026-09-14 08:00:00', ['power' => 25.0, 'amperage' => 2.0, 'energy_wh' => 12.0, 'energy_ah' => 1.0]);

        // Panel: base de 478 Wh en las tablas viejas, 1081 Ah el día 13.
        DB::table('hardware_power_generators_today')->insert(['hardware_device_id' => 6, 'date' => '2026-09-13', 'energy_wh' => 478.0, 'created_at' => '2026-09-13 00:00:38', 'updated_at' => '2026-09-13 11:58:57']);
        $this->dia(4, '2026-09-13', 757.0, 1081.43);
        $this->lectura(4, '2026-09-13 11:30:00', ['power' => 100.0]);
        $this->lectura(4, '2026-09-13 14:25:31', ['power' => 60.0, 'energy_wh' => 14.0, 'energy_ah' => 0.4]);
        $this->dia(4, '2026-09-14', 549.0, 16.23);
        $this->lectura(4, '2026-09-14 12:00:00', ['power' => 60.0, 'energy_wh' => 12.0, 'energy_ah' => 0.3]);

        // Batería: base de 36 Ah y Wh con la descarga en valor absoluto.
        DB::table('hardware_power_generators_solar')->insert(['hardware_device_id' => 6, 'date' => '2026-09-13', 'day_charging_amp_hours' => 36.0, 'created_at' => '2026-09-13 11:58:57', 'updated_at' => '2026-09-13 11:58:57']);
        $this->dia(11, '2026-09-13', 625.96, 57.0);
        $this->lectura(11, '2026-09-13 15:00:00', ['amperage' => -1.9, 'energy_wh' => 2.8, 'energy_ah' => 2.0]);
        $this->dia(11, '2026-09-14', 518.21, 41.0);
        $this->lectura(11, '2026-09-14 12:00:00', ['amperage' => 3.0, 'energy_wh' => 4.1, 'energy_ah' => 1.0]);

        $this->sesion(4, 1000.0, 999.0);
        $this->sesion(7, 50000.0, 4000.0);
        $this->sesion(11, 9999.0, 777.0);
    }

    #[Test]
    public function consumption_days_are_rebuilt_from_their_readings(): void
    {
        $this->montaLosDatosRotos();

        $this->ejecutaLaMigracion();

        // 24 W × 300 s + 24 W × 900 s (tope) = 8 Wh; 2 A × 1200 s = 0,6667 Ah.
        $this->assertEqualsWithDelta(8.0, $this->valorDelDia(7, '2026-09-08')['wh'], 0.0001);
        $this->assertEqualsWithDelta(2 / 3, $this->valorDelDia(7, '2026-09-08')['ah'], 0.0001);
        $this->assertSame('derived', $this->valorDelDia(7, '2026-09-08')['ws']);

        $this->assertEqualsWithDelta(12.0, $this->valorDelDia(7, '2026-09-14')['wh'], 0.0001, 'Sin los 108 Wh del día anterior');
        $this->assertEqualsWithDelta(999.0, $this->valorDelDia(7, '2026-09-05')['wh'], 0.0001, 'Antes del 7 no se toca');
    }

    #[Test]
    public function panel_and_battery_days_square_with_their_nominal_voltage(): void
    {
        $this->montaLosDatosRotos();

        $this->ejecutaLaMigracion();

        $panel13 = $this->valorDelDia(4, '2026-09-13');
        $this->assertEqualsWithDelta(492.0, $panel13['wh'], 0.0001, '478 de la mañana + 14 de la tarde');
        $this->assertEqualsWithDelta(20.5, $panel13['ah'], 0.0001, '492 Wh ÷ 24 V, no 1081 Ah');

        $panel14 = $this->valorDelDia(4, '2026-09-14');
        $this->assertEqualsWithDelta(12.0, $panel14['wh'], 0.0001);
        $this->assertEqualsWithDelta(0.5, $panel14['ah'], 0.0001);

        $bateria13 = $this->valorDelDia(11, '2026-09-13');
        $this->assertEqualsWithDelta(38.0, $bateria13['ah'], 0.0001, '36 de la mañana + 2 de la tarde');
        $this->assertEqualsWithDelta(456.0, $bateria13['wh'], 0.0001, '38 Ah × 12 V');
        $this->assertSame('derived', $bateria13['as']);

        $this->assertEqualsWithDelta(12.0, $this->valorDelDia(11, '2026-09-14')['wh'], 0.0001);
    }

    #[Test]
    public function wrong_readings_since_the_new_contract_are_fixed(): void
    {
        $this->montaLosDatosRotos();

        $this->ejecutaLaMigracion();

        $panel = DB::table('hardware_energy_readings')->where('hardware_energy_id', 4)->where('created_at', '2026-09-13 14:25:31')->first();
        $this->assertEqualsWithDelta(14 / 24, (float) $panel->energy_ah, 0.0001);

        $bateria = DB::table('hardware_energy_readings')->where('hardware_energy_id', 11)->where('created_at', '2026-09-13 15:00:00')->first();
        $this->assertEqualsWithDelta(24.0, (float) $bateria->energy_wh, 0.0001, 'Positivo: 2 Ah de carga × 12 V');
    }

    #[Test]
    public function lifetime_totals_square_with_the_days(): void
    {
        $this->montaLosDatosRotos();

        $this->ejecutaLaMigracion();

        $panel = DB::table('hardware_energy_historical')->where('hardware_energy_id', 4)->first();
        $this->assertEqualsWithDelta(1000.0, (float) $panel->energy_wh, 0.0001, 'Los Wh del odómetro no se tocan');
        $this->assertEqualsWithDelta(1000 / 24, (float) $panel->energy_ah, 0.0001);

        $bateria = DB::table('hardware_energy_historical')->where('hardware_energy_id', 11)->first();
        $this->assertEqualsWithDelta(39.0, (float) $bateria->energy_ah, 0.0001, 'Suma de sus días: 38 + 1');
        $this->assertEqualsWithDelta(468.0, (float) $bateria->energy_wh, 0.0001);

        $consumo = DB::table('hardware_energy_historical')->where('hardware_energy_id', 7)->first();
        $this->assertEqualsWithDelta(999.0 + 8.0 + 12.0, (float) $consumo->energy_wh, 0.0001);
    }

    #[Test]
    public function running_it_twice_gives_the_same_result(): void
    {
        $this->montaLosDatosRotos();

        $this->ejecutaLaMigracion();
        $primera = DB::table('hardware_energy_today')->orderBy('id')->get(['energy_wh', 'energy_ah'])->toArray();

        $this->ejecutaLaMigracion();
        $segunda = DB::table('hardware_energy_today')->orderBy('id')->get(['energy_wh', 'energy_ah'])->toArray();

        $this->assertEquals($primera, $segunda);
    }

    #[Test]
    public function without_the_rover_it_does_nothing(): void
    {
        $this->ejecutaLaMigracion();

        $this->assertSame(0, DB::table('hardware_energy_today')->count());
    }
}
