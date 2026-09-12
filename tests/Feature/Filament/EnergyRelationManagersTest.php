<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\HistoricalRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\ReadingsRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\TodayRelationManager;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de los Relation Managers de telemetría de 3 niveles para HardwareEnergy:
 * - ReadingsRelationManager (lecturas granulares y marcas sospechosas)
 * - TodayRelationManager (resúmenes diarios y extremos)
 * - HistoricalRelationManager (sesiones históricas de odómetro)
 */
class EnergyRelationManagersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $energy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($this->user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Solar Monitor ESP32',
        ]);

        $this->energy = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
        ]);
    }

    #[Test]
    public function readings_relation_manager_renders_telemetry_and_suspicious_mark(): void
    {
        $normalReading = HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->energy->id,
            'voltage' => 24.5,
            'amperage' => 4.2,
            'power' => 102.9,
            'delta_seconds' => 10,
            'energy_wh' => 0.28,
            'energy_ah' => 0.011,
            'energy_source' => 'measured',
            'voltage_source' => 'measured',
            'is_suspicious' => false,
            'created_at' => now(),
        ]);

        $suspiciousReading = HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->energy->id,
            'voltage' => 120.0,
            'amperage' => 50.0,
            'power' => 6000.0,
            'delta_seconds' => 10,
            'energy_wh' => 16.6,
            'energy_ah' => 0.138,
            'energy_source' => 'measured',
            'voltage_source' => 'measured',
            'is_suspicious' => true,
            'suspicious_reason' => 'Voltaje 120V fuera de rango',
            'created_at' => now()->subMinute(),
        ]);

        Livewire::test(ReadingsRelationManager::class, [
            'ownerRecord' => $this->energy,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])
            ->assertCanSeeTableRecords([$normalReading, $suspiciousReading])
            ->assertSee('102.9')
            ->assertSee('6000')
            ->assertSee('Sospechosa');
    }

    #[Test]
    public function today_relation_manager_renders_daily_aggregates_and_extremes(): void
    {
        $todayRecord = HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->energy->id,
            'date' => now()->toDateString(),
            'readings_count' => 120,
            'energy_wh' => 350.50,
            'energy_ah' => 14.60,
            'voltage_min' => 22.1,
            'voltage_max' => 28.4,
            'voltage_avg' => 25.2,
            'amperage_min' => 0.0,
            'amperage_max' => 8.5,
            'amperage_avg' => 3.2,
            'power_min' => 0.0,
            'power_max' => 240.0,
            'power_avg' => 80.0,
        ]);

        Livewire::test(TodayRelationManager::class, [
            'ownerRecord' => $this->energy,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])
            ->assertCanSeeTableRecords([$todayRecord])
            ->assertSee('350.5')
            ->assertSee('120');
    }

    #[Test]
    public function historical_relation_manager_renders_odometer_sessions(): void
    {
        $session = HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->energy->id,
            'session_index' => 1,
            'days_operating' => 45,
            'readings_count' => 54000,
            'energy_wh' => 125000.0,
            'energy_ah' => 5200.0,
            'number_battery_full_charges' => 30,
            'number_battery_over_discharges' => 2,
            'first_reading_at' => now()->subDays(45),
            'last_reading_at' => now(),
        ]);

        Livewire::test(HistoricalRelationManager::class, [
            'ownerRecord' => $this->energy,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])
            ->assertCanSeeTableRecords([$session])
            ->assertSee('125000')
            ->assertSee('45');
    }
}
