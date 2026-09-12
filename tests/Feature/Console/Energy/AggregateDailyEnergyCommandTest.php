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

class AggregateDailyEnergyCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createDeviceWithElement(bool $autoCalculate = true, string $role = HardwareEnergy::ROLE_GENERATOR): array
    {
        $device = HardwareDevice::create(['name' => 'Monitor Test']);
        $element = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => $role,
            'sensor_position' => 0,
            'auto_calculate_history' => $autoCalculate,
            'is_active' => true,
        ]);

        return [$device, $element];
    }

    #[Test]
    public function it_consolidates_yesterday_readings_into_today_and_historical_for_auto_calculate_elements(): void
    {
        [$device, $element] = $this->createDeviceWithElement(autoCalculate: true);

        $yesterday = Carbon::yesterday();

        // 2 lecturas normales de ayer
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0,
            'amperage' => 2.0,
            'power' => 48.0,
            'energy_wh' => 4.0,
            'energy_ah' => 0.1667,
            'temperature' => 25.0,
            'is_suspicious' => false,
            'created_at' => $yesterday->copy()->setHour(10),
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 26.0,
            'amperage' => 3.0,
            'power' => 78.0,
            'energy_wh' => 6.5,
            'energy_ah' => 0.25,
            'temperature' => 30.0,
            'is_suspicious' => false,
            'created_at' => $yesterday->copy()->setHour(14),
        ]);

        // 1 lectura sospechosa que NO debe sumarse
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 25.0,
            'amperage' => 99.0,
            'power' => 2475.0,
            'energy_wh' => 500.0,
            'energy_ah' => 20.0,
            'temperature' => 85.0,
            'is_suspicious' => true,
            'suspicious_reason' => 'anomalía de prueba',
            'created_at' => $yesterday->copy()->setHour(15),
        ]);

        $this->artisan('energy:aggregate-daily')
            ->assertSuccessful()
            ->expectsOutputToContain('Consolidando agregados de energía para la fecha '.$yesterday->toDateString());

        // Verificamos HardwareEnergyToday
        $today = HardwareEnergyToday::where('hardware_energy_id', $element->id)
            ->where('date', $yesterday->toDateString())
            ->first();

        $this->assertNotNull($today);
        $this->assertSame(2, $today->readings_count);
        $this->assertEquals(10.5, (float) $today->energy_wh);
        $this->assertEqualsWithDelta(0.4167, (float) $today->energy_ah, 0.001);
        $this->assertEquals(24.0, (float) $today->voltage_min);
        $this->assertEquals(26.0, (float) $today->voltage_max);
        $this->assertEquals(48.0, (float) $today->power_min);
        $this->assertEquals(78.0, (float) $today->power_max);
        $this->assertEquals(25.0, (float) $today->temperature_min);
        $this->assertEquals(30.0, (float) $today->temperature_max);

        // Verificamos HardwareEnergyHistorical
        $historical = HardwareEnergyHistorical::where('hardware_energy_id', $element->id)->first();
        $this->assertNotNull($historical);
        $this->assertSame(1, $historical->days_operating);
        $this->assertSame(2, $historical->readings_count);
        $this->assertEquals(10.5, (float) $historical->energy_wh);
        $this->assertEqualsWithDelta(0.4167, (float) $historical->energy_ah, 0.001);
        $this->assertEquals(24.0, (float) $historical->voltage_min);
        $this->assertEquals(26.0, (float) $historical->voltage_max);
    }

    #[Test]
    public function it_skips_elements_with_auto_calculate_history_false_unless_all_option_is_used(): void
    {
        [$device, $element] = $this->createDeviceWithElement(autoCalculate: false);

        $yesterday = Carbon::yesterday();

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0,
            'amperage' => 1.0,
            'power' => 24.0,
            'energy_wh' => 2.0,
            'is_suspicious' => false,
            'created_at' => $yesterday->copy()->setHour(12),
        ]);

        // Sin --all no debe procesar el elemento
        $this->artisan('energy:aggregate-daily')
            ->assertSuccessful()
            ->expectsOutputToContain('No hay elementos energéticos que procesar');

        $this->assertDatabaseMissing('hardware_energy_today', [
            'hardware_energy_id' => $element->id,
        ]);

        // Con --all sí debe procesarlo
        $this->artisan('energy:aggregate-daily --all')
            ->assertSuccessful()
            ->expectsOutputToContain('Consolidando agregados de energía para la fecha '.$yesterday->toDateString());

        $this->assertDatabaseHas('hardware_energy_today', [
            'hardware_energy_id' => $element->id,
            'readings_count' => 1,
        ]);
    }

    #[Test]
    public function it_supports_custom_date_and_specific_element(): void
    {
        [$deviceA, $elementA] = $this->createDeviceWithElement(autoCalculate: true);
        [$deviceB, $elementB] = $this->createDeviceWithElement(autoCalculate: true);

        $customDate = Carbon::parse('2026-08-15');

        HardwareEnergyReading::create([
            'hardware_device_id' => $deviceA->id,
            'hardware_energy_id' => $elementA->id,
            'power' => 50.0,
            'energy_wh' => 5.0,
            'is_suspicious' => false,
            'created_at' => $customDate->copy()->setHour(10),
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $deviceB->id,
            'hardware_energy_id' => $elementB->id,
            'power' => 100.0,
            'energy_wh' => 10.0,
            'is_suspicious' => false,
            'created_at' => $customDate->copy()->setHour(11),
        ]);

        // Ejecutar sólo para elementA en customDate
        $this->artisan("energy:aggregate-daily --date=2026-08-15 --element={$elementA->id}")
            ->assertSuccessful()
            ->expectsOutputToContain('Consolidando agregados de energía para la fecha 2026-08-15 (1 elementos)');

        $this->assertDatabaseHas('hardware_energy_today', [
            'hardware_energy_id' => $elementA->id,
            'date' => '2026-08-15',
            'readings_count' => 1,
        ]);

        $this->assertDatabaseMissing('hardware_energy_today', [
            'hardware_energy_id' => $elementB->id,
            'date' => '2026-08-15',
        ]);
    }

    #[Test]
    public function it_handles_invalid_date_format_gracefully(): void
    {
        $this->artisan('energy:aggregate-daily --date=fecha-invalida')
            ->assertFailed()
            ->expectsOutputToContain('Formato de fecha inválido. Utilice el formato YYYY-MM-DD.');
    }
}
