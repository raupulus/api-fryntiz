<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Widgets\EnergyHistoricalChart;
use App\Filament\Admin\Widgets\EnergyStatsWidget;
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
 * Pruebas de los widgets de analítica de energía en Filament Admin:
 * - EnergyStatsWidget (consumo, generación, balance y acumulados)
 * - EnergyHistoricalChart (series temporales de 30 días)
 */
class EnergyWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $normalUser;

    private HardwareDevice $device;

    private HardwareEnergy $generator;

    private HardwareEnergy $load;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->adminUser = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->normalUser = User::factory()->create(['role_id' => 3, 'is_active' => true]);

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->device = HardwareDevice::create([
            'user_id' => $this->adminUser->id,
            'name' => 'Solar Rig 1',
        ]);

        $this->generator = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->load = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 1,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function only_admins_can_view_energy_widgets(): void
    {
        $this->actingAs($this->adminUser);
        $this->assertTrue(EnergyStatsWidget::canView());
        $this->assertTrue(EnergyHistoricalChart::canView());

        $this->actingAs($this->normalUser);
        $this->assertFalse(EnergyStatsWidget::canView());
        $this->assertFalse(EnergyHistoricalChart::canView());
    }

    #[Test]
    public function stats_widget_renders_current_today_and_historical_metrics(): void
    {
        $this->actingAs($this->adminUser);

        // Lectura actual de generación (250 W) y de consumo (100 W) -> Balance +150 W
        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'voltage' => 25.0,
            'amperage' => 10.0,
            'power' => 250.0,
            'delta_seconds' => 10,
            'energy_wh' => 0.69,
            'battery_percentage' => 85,
            'created_at' => now(),
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->load->id,
            'voltage' => 12.0,
            'amperage' => 8.33,
            'power' => 100.0,
            'delta_seconds' => 10,
            'energy_wh' => 0.28,
            'created_at' => now(),
        ]);

        // Agregados de hoy: 1200 Wh generación, 800 Wh consumo
        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'date' => now()->toDateString(),
            'readings_count' => 500,
            'energy_wh' => 1200.0,
            'energy_ah' => 48.0,
            'power_max' => 300.0,
            'battery_percentage_min' => 75,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->load->id,
            'date' => now()->toDateString(),
            'readings_count' => 500,
            'energy_wh' => 800.0,
            'energy_ah' => 66.0,
            'power_max' => 120.0,
        ]);

        // Histórico de odómetro
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'session_index' => 1,
            'days_operating' => 60,
            'readings_count' => 150000,
            'energy_wh' => 85000.0,
            'energy_ah' => 3400.0,
            'number_battery_full_charges' => 25,
            'number_battery_over_discharges' => 1,
            'first_reading_at' => now()->subDays(60),
            'last_reading_at' => now(),
        ]);

        Livewire::test(EnergyStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Consumo (ahora)')
            ->assertSee('100.00 W')
            ->assertSee('Generación (ahora)')
            ->assertSee('250.00 W')
            ->assertSee('Balance neto (ahora)')
            ->assertSee('+150.00 W')
            ->assertSee('Superávit energético')
            ->assertSee('Batería media (ahora)')
            ->assertSee('85 %')
            ->assertSee('Consumo (hoy)')
            ->assertSee('800.00 Wh')
            ->assertSee('Generación (hoy)')
            ->assertSee('1,200.00 Wh')
            ->assertSee('Días en operación')
            ->assertSee('60')
            ->assertSee('25 / 1');
    }

    #[Test]
    public function historical_chart_renders_without_errors(): void
    {
        $this->actingAs($this->adminUser);

        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'date' => now()->toDateString(),
            'readings_count' => 10,
            'energy_wh' => 500.0,
            'energy_ah' => 20.0,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->load->id,
            'date' => now()->toDateString(),
            'readings_count' => 10,
            'energy_wh' => 250.0,
            'energy_ah' => 20.0,
        ]);

        Livewire::test(EnergyHistoricalChart::class)
            ->assertSuccessful()
            ->assertSee('Generación vs consumo');
    }
}
