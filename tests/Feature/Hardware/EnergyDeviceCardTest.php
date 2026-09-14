<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La tarjeta de un dispositivo en `/hardware/energy`, cuando no tiene
 * generador configurado.
 *
 * Antes toda tarjeta enseñaba "Generando ahora"/"Generado hoy" aunque el
 * dispositivo sólo midiera consumo (siempre en 0, sin decir nada), el
 * "resumen rápido" tenía un hueco de "Generado" igual de vacío, y varios
 * consumos del mismo monitor se sumaban en una única cifra sin decir cuál
 * pesaba más.
 */
class EnergyDeviceCardTest extends TestCase
{
    use RefreshDatabase;

    private function loadElement(HardwareDevice $monitor, ?HardwareDevice $monitorized, int $channel): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $monitor->id,
            'hardware_device_monitorized_id' => $monitorized?->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => $channel,
            'is_active' => true,
        ]);
    }

    private function generatorElement(HardwareDevice $monitor): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $monitor->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'is_active' => true,
        ]);
    }

    private function readingNow(HardwareDevice $monitor, HardwareEnergy $element, float $watts): void
    {
        HardwareEnergyReading::create([
            'hardware_device_id' => $monitor->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 12.0,
            'amperage' => $watts / 12.0,
            'power' => $watts,
            'created_at' => now()->subMinutes(5),
        ]);
    }

    private function readingToday(HardwareDevice $monitor, HardwareEnergy $element, float $wh): void
    {
        HardwareEnergyToday::create([
            'hardware_device_id' => $monitor->id,
            'hardware_energy_id' => $element->id,
            'energy_wh' => $wh,
            'energy_ah' => $wh / 12.0,
            'date' => today(),
        ]);
    }

    #[Test]
    public function generating_rows_are_hidden_without_a_generator(): void
    {
        $monitor = HardwareDevice::create(['name' => 'Solo consumo']);
        $load = $this->loadElement($monitor, null, 0);
        $this->readingNow($monitor, $load, 5.0);
        $this->readingToday($monitor, $load, 10.0);

        $this->get('/hardware/energy')
            ->assertOk()
            ->assertDontSee('Generando ahora')
            ->assertDontSee('Generado hoy')
            ->assertSee('Consumiendo ahora');
    }

    #[Test]
    public function generating_rows_are_shown_with_a_generator(): void
    {
        $monitor = HardwareDevice::create(['name' => 'Con generador']);
        $gen = $this->generatorElement($monitor);
        $this->readingNow($monitor, $gen, 20.0);
        $this->readingToday($monitor, $gen, 200.0);

        $this->get('/hardware/energy')
            ->assertOk()
            ->assertSee('Generando ahora')
            ->assertSee('Generado hoy');
    }

    #[Test]
    public function decimal_values_show_at_most_two_decimals_without_forcing_them(): void
    {
        $monitor = HardwareDevice::create(['name' => 'Decimales']);
        $load = $this->loadElement($monitor, null, 0);

        // Entero exacto: no se fuerza ".00".
        $this->readingNow($monitor, $load, 5.0);
        // Más de dos decimales: se redondea a dos.
        $this->readingToday($monitor, $load, 100.256);

        $html = $this->get('/hardware/energy')->assertOk()->getContent();

        $this->assertStringContainsString('5 W', $html);
        $this->assertStringNotContainsString('5.00 W', $html);
        $this->assertStringNotContainsString('5.0 W', $html);

        $this->assertStringContainsString('100.26 Wh', $html);
        $this->assertStringNotContainsString('100.256 Wh', $html);
    }

    #[Test]
    public function without_a_generator_the_quick_summary_shows_the_devices_own_status(): void
    {
        $monitor = HardwareDevice::create([
            'name' => 'Raspberry consumo',
            'cpu' => 12.345,
            'temp' => 41.2,
            // Sin battery_level: no debe salir "Batería" en el resumen.
            'ram' => 60.0,
        ]);
        $load = $this->loadElement($monitor, null, 0);
        $this->readingNow($monitor, $load, 1.0);

        $html = $this->get('/hardware/energy')->assertOk()->getContent();

        // Prioridad cpu > temperatura > batería > ram, las tres primeras con dato.
        $this->assertStringContainsString('12.35%', $html);
        $this->assertStringContainsString('41.2°C', $html);
        $this->assertStringContainsString('60%', $html);
    }

    #[Test]
    public function a_generator_device_does_not_get_its_own_status_badges(): void
    {
        $monitor = HardwareDevice::create([
            'name' => 'Con generador y cpu',
            'cpu' => 33.0,
        ]);
        $gen = $this->generatorElement($monitor);
        $this->readingNow($monitor, $gen, 10.0);

        $html = $this->get('/hardware/energy')->assertOk()->getContent();

        // El resumen sigue siendo Generado/Consumido/Batería, no CPU.
        $this->assertStringNotContainsString('device_thermostat', $html);
        $this->assertStringNotContainsString('>33%<', $html);
    }

    #[Test]
    public function several_loads_are_listed_separately_with_the_monitored_devices_name_and_channel(): void
    {
        $monitor = HardwareDevice::create(['name' => 'Monitor de consumos']);
        $fridge = HardwareDevice::create(['name' => 'Nevera']);
        $router = HardwareDevice::create(['name' => 'Router']);

        $loadFridge = $this->loadElement($monitor, $fridge, 1);
        $loadRouter = $this->loadElement($monitor, $router, 2);

        $this->readingNow($monitor, $loadFridge, 5.0);
        $this->readingToday($monitor, $loadFridge, 100.0);

        $this->readingNow($monitor, $loadRouter, 6.2);
        $this->readingToday($monitor, $loadRouter, 50.0);

        $this->get('/hardware/energy')
            ->assertOk()
            ->assertSee('Nevera · canal 1')
            ->assertSee('Router · canal 2');
    }

    /**
     * Con un único consumo no hace falta distinguir nada: se queda como
     * antes, sin cabecera de nombre/canal delante de las barras.
     */
    #[Test]
    public function a_single_load_does_not_show_a_channel_label(): void
    {
        $monitor = HardwareDevice::create(['name' => 'Un solo consumo']);
        $fridge = HardwareDevice::create(['name' => 'Nevera Sola']);
        $load = $this->loadElement($monitor, $fridge, 1);
        $this->readingNow($monitor, $load, 5.0);

        $this->get('/hardware/energy')
            ->assertOk()
            ->assertSee('Consumiendo ahora')
            ->assertDontSee('Nevera Sola');
    }
}
