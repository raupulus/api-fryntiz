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
use App\Models\Hardware\HardwareType;
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

        // El widget sólo suma la instalación solar.
        $this->device = HardwareDevice::create([
            'user_id' => $this->adminUser->id,
            'name' => 'Solar Rig 1',
            'hardware_type_id' => HardwareType::firstOrCreate(['slug' => HardwareType::SOLAR_CONTROLLER_SLUG], ['name' => 'Controlador Solar'])->id,
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

    /**
     * Una Raspberry Pi enchufada a la red de casa que mide su propio consumo no
     * es consumo de la instalación solar.
     */
    #[Test]
    public function stats_widget_leaves_out_devices_on_the_mains(): void
    {
        $this->actingAs($this->adminUser);

        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->load->id,
            'voltage' => 12.0,
            'amperage' => 2.0,
            'power' => 24.0,
            'created_at' => now(),
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->load->id,
            'date' => now()->toDateString(),
            'readings_count' => 10,
            'energy_wh' => 300.0,
        ]);

        $pi = HardwareDevice::create([
            'user_id' => $this->adminUser->id,
            'name' => 'Raspberry Pi 5',
            'hardware_type_id' => HardwareType::firstOrCreate(['slug' => 'micro-pc'], ['name' => 'Micro PC'])->id,
        ]);
        $piLoad = HardwareEnergy::create([
            'hardware_device_id' => $pi->id,
            'hardware_device_monitorized_id' => $pi->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 5.0,
            'is_active' => true,
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $pi->id,
            'hardware_energy_id' => $piLoad->id,
            'voltage' => 5.1,
            'amperage' => 0.6,
            'power' => 3.0,
            'created_at' => now(),
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $pi->id,
            'hardware_energy_id' => $piLoad->id,
            'date' => now()->toDateString(),
            'readings_count' => 10,
            'energy_wh' => 65.0,
        ]);

        Livewire::test(EnergyStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('24.00 W')
            ->assertDontSee('27.00 W')
            ->assertSee('300.00 Wh')
            ->assertDontSee('365.00 Wh');
    }

    /**
     * La carga es la de la batería de ahora, no una media con porcentajes
     * viejos que el generador y el consumo guardaron cuando se replicaba en sus
     * lecturas: con la batería al 68 % marcaba 89 %.
     */
    #[Test]
    public function stats_widget_battery_percentage_is_the_batterys_current_one(): void
    {
        $this->actingAs($this->adminUser);

        $battery = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);

        foreach ([[$this->generator, 100, now()->subDays(19)], [$this->load, 100, now()->subDays(11)], [$battery, 68, now()->subMinutes(2)]] as [$element, $percentage, $at]) {
            HardwareEnergyReading::create([
                'hardware_device_id' => $this->device->id,
                'hardware_energy_id' => $element->id,
                'voltage' => 12.5,
                'battery_percentage' => $percentage,
                'created_at' => $at,
            ]);
        }

        Livewire::test(EnergyStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('68 %')
            ->assertDontSee('89 %');
    }

    /**
     * El Renogy manda sus ciclos en el generador y en la batería: son los de
     * una sola batería y no se suman dos veces.
     */
    #[Test]
    public function stats_widget_counts_each_battery_cycles_once(): void
    {
        $this->actingAs($this->adminUser);

        $battery = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);

        foreach ([[$this->generator, 1350], [$battery, 1356]] as [$element, $full]) {
            HardwareEnergyHistorical::create([
                'hardware_device_id' => $this->device->id,
                'hardware_energy_id' => $element->id,
                'session_index' => 1,
                'number_battery_full_charges' => $full,
                'number_battery_over_discharges' => 26,
            ]);
        }

        // Un aparato de V1 sin elemento batería: sus ciclos van en el generador.
        $sunix = HardwareDevice::create([
            'user_id' => $this->adminUser->id,
            'name' => 'Sunix',
            'hardware_type_id' => $this->device->hardware_type_id,
        ]);
        $sunixGenerator = HardwareEnergy::create([
            'hardware_device_id' => $sunix->id,
            'hardware_device_monitorized_id' => $sunix->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'is_active' => true,
        ]);
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $sunix->id,
            'hardware_energy_id' => $sunixGenerator->id,
            'session_index' => 1,
            'number_battery_full_charges' => 791,
            'number_battery_over_discharges' => 49,
        ]);

        Livewire::test(EnergyStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('2147 / 75')
            ->assertDontSee('3497');
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
