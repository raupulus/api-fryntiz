<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Filament\Admin\Widgets\EnergyStatsWidget;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cómo se leen los acumulados en las dos pantallas que los enseñan.
 *
 * Había dos criterios distintos para el mismo dato: el panel público
 * (`/hardware/energy`) sumaba **todas** las sesiones del histórico y el widget
 * del panel de administración se quedaba con **la última**. Mientras no hubo
 * ningún reinicio de odómetro los dos daban lo mismo y nadie lo notó; al primer
 * reinicio uno de los dos pasa a mentir.
 *
 * El criterio bueno es sumar: el acumulado de un elemento es la suma de sus
 * sesiones, porque cada sesión guarda lo que se acumuló entre dos reinicios.
 * Los **días** son la excepción: ahí se coge el máximo, porque dos elementos
 * que llevan 1.700 días cada uno no hacen 3.400 días de instalación.
 *
 * Se prueba además que el porcentaje de batería sale del rol `battery`: el
 * panel lo sacaba de las lecturas de generación y de consumo, que con el
 * contrato nuevo ya no lo traen.
 */
class EnergyHistoricalReadingTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    private HardwareEnergy $generator;

    private HardwareEnergy $battery;

    protected function setUp(): void
    {
        parent::setUp();

        // El tipo es lo que mete al aparato en los totales de la instalación
        // solar del panel público. Sin él sería un consumo suelto más.
        $this->device = HardwareDevice::create([
            'name' => 'Controlador solar',
            'hardware_type_id' => HardwareType::create(['name' => 'Controlador Solar'])->id,
        ]);

        $this->generator = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->battery = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.8,
            'is_active' => true,
        ]);
    }

    /**
     * Dos sesiones de odómetro para el generador: 40 kWh antes del reinicio y
     * 5 kWh después. El acumulado real son 45 kWh.
     */
    private function dosSesionesDeGeneracion(): void
    {
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'session_index' => 1,
            'days_operating' => 700,
            'readings_count' => 1000,
            'energy_wh' => 40000.0,
            'energy_ah' => 0.0,
        ]);

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'session_index' => 2,
            'days_operating' => 30,
            'readings_count' => 50,
            'energy_wh' => 5000.0,
            'energy_ah' => 0.0,
        ]);
    }

    #[Test]
    public function the_public_panel_adds_up_every_odometer_session(): void
    {
        $this->dosSesionesDeGeneracion();

        // Una lectura reciente para que el dispositivo entre en la pantalla.
        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'voltage' => 24.0,
            'amperage' => 1.0,
            'power' => 24.0,
            'is_suspicious' => false,
        ]);

        $response = $this->get('/hardware/energy');
        $response->assertOk();

        // 40.000 + 5.000 = 45.000 Wh = 45,0 kWh
        $this->assertSame('45.0', $response->viewData('generator')->historical);
    }

    #[Test]
    public function the_public_panel_takes_the_largest_day_count_not_the_sum(): void
    {
        $this->dosSesionesDeGeneracion();

        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'voltage' => 24.0,
            'amperage' => 1.0,
            'power' => 24.0,
            'is_suspicious' => false,
        ]);

        $response = $this->get('/hardware/energy');

        // 700 y 30 días: la instalación lleva 700, no 730.
        $this->assertSame(700, $response->viewData('generator')->days_operating);
    }

    #[Test]
    public function the_public_panel_reads_the_battery_percentage_from_the_battery_role(): void
    {
        // El SOC sólo lo escribe el bloque `battery`. Si la pantalla lo busca
        // en las lecturas de generación se queda a cero para siempre.
        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->battery->id,
            'voltage' => 13.1,
            'amperage' => 2.0,
            'power' => 26.2,
            'battery_voltage' => 13.1,
            'battery_percentage' => 87,
            'is_suspicious' => false,
        ]);

        $response = $this->get('/hardware/energy');
        $response->assertOk();

        $this->assertSame('87', $response->viewData('generator')->battery_percentage);
    }

    #[Test]
    public function the_public_panel_reads_the_charge_cycles_from_the_battery_role(): void
    {
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->battery->id,
            'session_index' => 1,
            'days_operating' => 700,
            'readings_count' => 1000,
            'energy_wh' => 0.0,
            'energy_ah' => 1500.0,
            'number_battery_full_charges' => 1348,
            'number_battery_over_discharges' => 26,
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->battery->id,
            'voltage' => 13.1,
            'amperage' => 2.0,
            'power' => 26.2,
            'battery_percentage' => 87,
            'is_suspicious' => false,
        ]);

        $response = $this->get('/hardware/energy');

        $this->assertSame('1,348', $response->viewData('generator')->battery_full_charge);
    }

    #[Test]
    public function the_admin_widget_agrees_with_the_public_panel(): void
    {
        $this->dosSesionesDeGeneracion();

        HardwareEnergyToday::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'date' => Carbon::now('UTC')->toDateString(),
            'readings_count' => 1,
            'energy_wh' => 100.0,
            'energy_ah' => 0.0,
        ]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->generator->id,
            'voltage' => 24.0,
            'amperage' => 1.0,
            'power' => 24.0,
            'is_suspicious' => false,
        ]);

        $publico = $this->get('/hardware/energy')->viewData('generator');

        (new RolesTableSeeder)->run();
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true]);

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // El widget no revienta y enseña los mismos días que la pantalla
        // pública: 700, el máximo, no la suma de las dos sesiones.
        Livewire::actingAs($admin)
            ->test(EnergyStatsWidget::class)
            ->assertOk()
            ->assertSee('700');

        $this->assertSame(700, $publico->days_operating);
    }
}
