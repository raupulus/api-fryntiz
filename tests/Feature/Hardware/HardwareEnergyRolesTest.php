<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los papeles de un elemento de energía.
 *
 * Una fila de `hardware_energy` es **un papel de un dispositivo**, no el
 * dispositivo: el mismo aparato puede tener lo que produce (`generator`), lo que
 * gasta (`load`) y lo que almacena (`battery`).
 *
 * Hasta el 2026-09-07 había dos columnas diciendo lo mismo —`role` y
 * `is_generator`—, y la booleana no sabía expresar «batería»: la dejaba en
 * `false`, indistinguible de una carga. Y nada impedía crear cuatro generadores
 * del mismo aparato.
 */
class HardwareEnergyRolesTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = HardwareDevice::create(['name' => 'Raspberry Pi Pico W']);
    }

    private function element(string $role, int $channel = 0, ?HardwareDevice $monitored = null): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => ($monitored ?? $this->monitor)->id,
            'role' => $role,
            'sensor_position' => $channel,
        ]);
    }

    // ── La tabla ─────────────────────────────────────────────────────────────

    #[Test]
    public function the_duplicated_columns_no_longer_exist(): void
    {
        $this->assertFalse(
            Schema::hasColumn('hardware_energy', 'is_generator'),
            '`is_generator` duplicaba a `role` y no sabía decir «batería».',
        );

        $this->assertFalse(
            Schema::hasColumn('hardware_energy', 'name'),
            'El nombre se compone del aparato y su papel: `display_name`.',
        );
    }

    #[Test]
    public function the_third_role_is_called_battery(): void
    {
        $this->assertSame('battery', HardwareEnergy::ROLE_BATTERY);

        $this->assertSame(
            ['generator', 'load', 'battery'],
            HardwareEnergy::ROLES,
        );

        $battery = $this->element(HardwareEnergy::ROLE_BATTERY);

        $this->assertSame('battery', $battery->refresh()->role);
    }

    #[Test]
    public function a_device_can_have_all_three_roles(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->element(HardwareEnergy::ROLE_LOAD);
        $this->element(HardwareEnergy::ROLE_BATTERY);

        $this->assertSame(3, HardwareEnergy::where('hardware_device_id', $this->monitor->id)->count());
    }

    // ── La restricción ───────────────────────────────────────────────────────

    #[Test]
    public function the_same_role_cannot_repeat_on_the_same_channel(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR, channel: 0);

        $this->expectException(QueryException::class);

        $this->element(HardwareEnergy::ROLE_GENERATOR, channel: 0);
    }

    /**
     * El caso real: una Raspberry con un INA que mide tres cosas distintas, cada
     * una por su canal y cada una midiendo **otro** aparato.
     */
    #[Test]
    public function a_monitor_can_measure_several_loads_on_different_channels(): void
    {
        $fan = HardwareDevice::create(['name' => 'Ventilador']);
        $lamp = HardwareDevice::create(['name' => 'Lámpara']);
        $micro = HardwareDevice::create(['name' => 'Microcontrolador']);

        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1, monitored: $fan);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 2, monitored: $lamp);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 3, monitored: $micro);

        $this->assertSame(3, HardwareEnergy::where('hardware_device_id', $this->monitor->id)->count());
    }

    /**
     * En PostgreSQL dos `NULL` no chocan entre sí en un índice único, así que
     * con el canal nullable la restricción no habría servido de nada. Por eso
     * la columna es `NOT NULL` con `0` por defecto.
     */
    #[Test]
    public function the_channel_does_not_accept_nulls(): void
    {
        $element = $this->element(HardwareEnergy::ROLE_LOAD);

        $this->assertSame(0, $element->refresh()->sensor_position);

        $this->expectException(QueryException::class);

        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $this->monitor->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => null,
        ]);
    }

    // ── Cuántos admite cada papel ────────────────────────────────────────────

    #[Test]
    public function generator_and_battery_are_limited_to_one_and_load_is_not(): void
    {
        $this->assertSame(1, HardwareEnergy::LIMIT_PER_ROLE[HardwareEnergy::ROLE_GENERATOR]);
        $this->assertSame(1, HardwareEnergy::LIMIT_PER_ROLE[HardwareEnergy::ROLE_BATTERY]);
        $this->assertNull(HardwareEnergy::LIMIT_PER_ROLE[HardwareEnergy::ROLE_LOAD]);
    }

    // ── El nombre compuesto ──────────────────────────────────────────────────

    #[Test]
    public function the_name_comes_from_the_monitored_device_and_its_role(): void
    {
        $panel = HardwareDevice::create(['name' => 'Renogy Rover']);

        $element = $this->element(HardwareEnergy::ROLE_GENERATOR, monitored: $panel);

        $this->assertSame('Renogy Rover · generador', $element->load('monitorized')->display_name);
    }

    /**
     * `display_name` se pinta en listados y en el aviso de cada lectura: si
     * tirara de la relación sería una consulta por fila. Con el lazy loading
     * desactivado, además, reventaría en vez de ir despacio en silencio.
     */
    #[Test]
    public function the_name_does_not_load_relations_on_its_own(): void
    {
        $panel = HardwareDevice::create(['name' => 'Renogy Rover']);
        $this->element(HardwareEnergy::ROLE_GENERATOR, monitored: $panel);

        // Recién traído de la base de datos, sin relaciones cargadas.
        $unloaded = HardwareEnergy::query()->sole();

        $this->assertStringContainsString('Elemento #', $unloaded->display_name);
        $this->assertStringContainsString('generador', $unloaded->display_name);

        // Y con la relación cargada, el nombre de verdad.
        $this->assertStringContainsString(
            'Renogy Rover',
            $unloaded->load('monitorized')->display_name,
        );
    }

    #[Test]
    public function the_name_carries_the_channel_only_when_there_are_several(): void
    {
        $fan = HardwareDevice::create(['name' => 'Ventilador']);

        $withoutChannel = $this->element(HardwareEnergy::ROLE_LOAD, channel: 0, monitored: $fan);
        $withChannel = $this->element(HardwareEnergy::ROLE_BATTERY, channel: 2, monitored: $fan);

        $this->assertSame('Ventilador · consumo', $withoutChannel->load('monitorized')->display_name);
        $this->assertSame('Ventilador · batería · canal 2', $withChannel->load('monitorized')->display_name);
    }

    // ── Las relaciones del dispositivo ───────────────────────────────────────

    #[Test]
    public function the_devices_relations_filter_by_role(): void
    {
        $panel = HardwareDevice::create(['name' => 'Panel']);
        $router = HardwareDevice::create(['name' => 'Router']);
        $battery = HardwareDevice::create(['name' => 'Banco de baterías']);

        $this->element(HardwareEnergy::ROLE_GENERATOR, channel: 1, monitored: $panel);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 2, monitored: $router);
        $this->element(HardwareEnergy::ROLE_BATTERY, channel: 3, monitored: $battery);

        $generators = $this->monitor->hardwareEnergyGenerator()->pluck('hardware_devices.id');
        $loads = $this->monitor->hardwareEnergyLoad()->pluck('hardware_devices.id');

        $this->assertSame([$panel->id], $generators->all());

        // Antes era «todo lo que no sea generador», así que la batería contaba
        // como carga. Ahora no.
        $this->assertSame([$router->id], $loads->all());
    }
}
