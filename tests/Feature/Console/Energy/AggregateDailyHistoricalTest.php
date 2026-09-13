<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lo que la reconciliación de acumulados puede y no puede hacer.
 */
class AggregateDailyHistoricalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: HardwareDevice, 1: HardwareEnergy}
     */
    private function crearElemento(): array
    {
        $device = HardwareDevice::create(['name' => 'Monitor']);

        $element = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'auto_calculate_history' => true,
            'is_active' => true,
        ]);

        return [$device, $element];
    }

    private function resumenDiario(HardwareDevice $device, HardwareEnergy $element, string $fecha, float $wh): void
    {
        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'date' => $fecha,
            'readings_count' => 10,
            'energy_wh' => $wh,
            'energy_ah' => 0.0,
        ]);
    }

    #[Test]
    public function it_keeps_a_historical_total_the_daily_summaries_do_not_cover(): void
    {
        [$device, $element] = $this->crearElemento();

        // Lo que llegó del esquema viejo: 762 días y 14.000 Wh, sin un resumen
        // diario por cada uno de esos días.
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'days_operating' => 762,
            'readings_count' => 5000,
            'energy_wh' => 14000.0,
            'energy_ah' => 0.0,
        ]);

        $this->resumenDiario($device, $element, Carbon::yesterday('UTC')->toDateString(), 36.0);

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(762, $historical->days_operating, 'Los días no pueden bajar: faltan resúmenes de casi toda su historia.');
        $this->assertSame(14000.0, (float) $historical->energy_wh, 'El acumulado heredado no se sustituye por la suma de un solo día.');
    }

    #[Test]
    public function it_rebuilds_the_historical_total_when_asked_explicitly(): void
    {
        [$device, $element] = $this->crearElemento();

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'days_operating' => 762,
            'readings_count' => 5000,
            'energy_wh' => 14000.0,
            'energy_ah' => 0.0,
        ]);

        $this->resumenDiario($device, $element, Carbon::yesterday('UTC')->toDateString(), 36.0);

        $this->artisan('energy:aggregate-daily --rebuild')->assertSuccessful();

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(1, $historical->days_operating);
        $this->assertSame(36.0, (float) $historical->energy_wh);
    }

    #[Test]
    public function it_lowers_a_historical_total_when_the_summaries_cover_all_of_it(): void
    {
        [$device, $element] = $this->crearElemento();

        // Dos días registrados y dos resúmenes: aquí sí se puede recalcular, y
        // el acumulado tiene que corregirse a la baja.
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'days_operating' => 2,
            'readings_count' => 20,
            'energy_wh' => 9999.0,
            'energy_ah' => 0.0,
        ]);

        $this->resumenDiario($device, $element, Carbon::yesterday('UTC')->toDateString(), 100.0);
        $this->resumenDiario($device, $element, Carbon::yesterday('UTC')->subDay()->toDateString(), 50.0);

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(2, $historical->days_operating);
        $this->assertSame(150.0, (float) $historical->energy_wh);
    }

    #[Test]
    public function it_reconciles_each_odometer_session_with_only_its_own_days(): void
    {
        [$device, $element] = $this->crearElemento();

        $sesion1 = HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'days_operating' => 1,
            'readings_count' => 10,
            'energy_wh' => 0.0,
            'energy_ah' => 0.0,
        ]);
        $sesion1->created_at = Carbon::parse('2026-08-01 00:00:00', 'UTC');
        $sesion1->save();

        $sesion2 = HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 2,
            'days_operating' => 1,
            'readings_count' => 10,
            'energy_wh' => 0.0,
            'energy_ah' => 0.0,
        ]);
        $sesion2->created_at = Carbon::parse('2026-08-10 00:00:00', 'UTC');
        $sesion2->save();

        // Dos días antes del reinicio y uno después.
        $this->resumenDiario($device, $element, '2026-08-02', 100.0);
        $this->resumenDiario($device, $element, '2026-08-03', 200.0);
        $this->resumenDiario($device, $element, '2026-08-11', 50.0);

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $sesiones = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->orderBy('session_index')
            ->get();

        $this->assertSame(300.0, (float) $sesiones[0]->energy_wh, 'La primera sesión sólo cuenta sus días.');
        $this->assertSame(50.0, (float) $sesiones[1]->energy_wh, 'La segunda no puede quedarse con el total del elemento.');
        $this->assertSame(
            350.0,
            (float) $sesiones->sum('energy_wh'),
            'El acumulado del elemento es la suma de sus sesiones, sin duplicar.'
        );
    }

    #[Test]
    public function it_does_not_recalculate_a_session_fed_by_the_device_odometer(): void
    {
        [$device, $element] = $this->crearElemento();

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'days_operating' => 1745,
            'readings_count' => 900000,
            'energy_wh_source' => HardwareEnergyHistorical::SOURCE_DEVICE,
            'energy_wh' => 523755.0,
            'energy_ah' => 0.0,
        ]);

        $this->resumenDiario($device, $element, Carbon::yesterday('UTC')->toDateString(), 10.0);

        // Ni siquiera con --rebuild: los totales del aparato son suyos.
        $this->artisan('energy:aggregate-daily --rebuild')->assertSuccessful();

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(523755.0, (float) $historical->energy_wh);
        $this->assertSame(1745, $historical->days_operating);
    }

    #[Test]
    public function it_cuts_the_day_in_utc(): void
    {
        [$device, $element] = $this->crearElemento();

        $ayer = Carbon::yesterday('UTC');

        // 23:30 UTC de ayer: en Madrid ya es hoy, pero se guarda en UTC y es
        // ayer quien tiene que contarla.
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0,
            'amperage' => 1.0,
            'power' => 24.0,
            'energy_wh' => 5.0,
            'energy_ah' => 0.2,
            'is_suspicious' => false,
            'created_at' => $ayer->copy()->setTime(23, 30),
        ]);

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $this->assertDatabaseHas('hardware_energy_today', [
            'hardware_energy_id' => $element->id,
            'date' => $ayer->toDateString(),
            'readings_count' => 1,
        ]);
    }

    #[Test]
    public function it_does_not_overwrite_a_daily_total_declared_by_the_device(): void
    {
        // El fallo que esto fija: el cierre nocturno hacía
        // `max(lo que había, la suma de nuestras lecturas)` sobre el resumen del
        // día. Un controlador que declaraba 800 Wh amanecía con 1.500 porque
        // nuestras lecturas —integradas con el intervalo supuesto— sumaban más.
        [$device, $element] = $this->crearElemento();

        $ayer = Carbon::yesterday('UTC');

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'date' => $ayer->toDateString(),
            'readings_count' => 3,
            'energy_wh_source' => HardwareEnergyHistorical::SOURCE_DEVICE,
            'energy_ah_source' => HardwareEnergyHistorical::SOURCE_DEVICE,
            'energy_wh' => 800.0,
            'energy_ah' => 33.0,
        ]);

        foreach (range(1, 3) as $ignorado) {
            HardwareEnergyReading::create([
                'hardware_device_id' => $device->id,
                'hardware_energy_id' => $element->id,
                'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
                'delta_seconds' => 60, 'energy_wh' => 500.0, 'energy_ah' => 20.0,
                'energy_source' => 'derived', 'voltage_source' => 'measured',
                'created_at' => $ayer->copy()->setTime(12, 0),
            ]);
        }

        $this->artisan('energy:aggregate-daily', ['--date' => $ayer->toDateString()])->assertSuccessful();

        $resumen = HardwareEnergyToday::query()->where('hardware_energy_id', $element->id)->firstOrFail();

        $this->assertSame(800.0, (float) $resumen->energy_wh, 'El total del aparato no se toca.');
        $this->assertSame(33.0, (float) $resumen->energy_ah);
    }

    #[Test]
    public function it_does_rebuild_a_daily_total_that_we_calculate_ourselves(): void
    {
        // Y el contrapunto: si el resumen es nuestro, el cron sí lo rehace.
        [$device, $element] = $this->crearElemento();

        $ayer = Carbon::yesterday('UTC');

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'date' => $ayer->toDateString(),
            'readings_count' => 1,
            'energy_wh' => 10.0,
            'energy_ah' => 1.0,
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
            'delta_seconds' => 60, 'energy_wh' => 96.0, 'energy_ah' => 4.0,
            'energy_source' => 'derived', 'voltage_source' => 'measured',
            'created_at' => $ayer->copy()->setTime(12, 0),
        ]);

        $this->artisan('energy:aggregate-daily', ['--date' => $ayer->toDateString()])->assertSuccessful();

        $resumen = HardwareEnergyToday::query()->where('hardware_energy_id', $element->id)->firstOrFail();

        $this->assertSame(96.0, (float) $resumen->energy_wh);
    }

    #[Test]
    public function a_declared_daily_total_only_freezes_the_magnitude_it_declares(): void
    {
        // Wh del aparato, Ah calculados: cada uno por su lado, igual que en el
        // acumulado de por vida.
        [$device, $element] = $this->crearElemento();

        $ayer = Carbon::yesterday('UTC');

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'date' => $ayer->toDateString(),
            'readings_count' => 1,
            'energy_wh_source' => HardwareEnergyHistorical::SOURCE_DEVICE,
            'energy_wh' => 800.0,
            'energy_ah' => 1.0,
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
            'delta_seconds' => 60, 'energy_wh' => 5000.0, 'energy_ah' => 40.0,
            'energy_source' => 'derived', 'voltage_source' => 'measured',
            'created_at' => $ayer->copy()->setTime(12, 0),
        ]);

        $this->artisan('energy:aggregate-daily', ['--date' => $ayer->toDateString()])->assertSuccessful();

        $resumen = HardwareEnergyToday::query()->where('hardware_energy_id', $element->id)->firstOrFail();

        $this->assertSame(800.0, (float) $resumen->energy_wh, 'Los Wh los declara el aparato.');
        $this->assertSame(40.0, (float) $resumen->energy_ah, 'Los Ah los calculamos nosotros.');
    }
}
