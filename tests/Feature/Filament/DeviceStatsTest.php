<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers\TokensRelationManager;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La ficha de un dispositivo: el estado arriba y en tarjetas.
 *
 * Eran diez `TextInput` deshabilitados dentro de una sección colapsada al final
 * de la página: lectura disfrazada de formulario, y encima escondida. Ahora son
 * tarjetas de sólo lectura en lo alto de la ficha, y cada una se oculta si su
 * valor es `null` —un cacharro que no mide CPU no tiene por qué enseñar un
 * hueco.
 *
 * Lo que hay que garantizar por encima de todo es que siguen siendo lectura:
 * guardar la ficha no puede escribir en las columnas que rellena la API.
 */
class DeviceStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]));

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[Test]
    public function only_the_readings_the_device_has_reported_are_shown(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Pico Display',
            'temp' => 21.5,
            'cpu' => 12,
            'uptime' => 14_212_800,
        ]);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->assertSuccessful()
            ->assertSee('Temperatura')
            ->assertSee('CPU')
            ->assertSee('Encendido')
            // No mide memoria, disco ni batería: esas tarjetas no salen.
            ->assertDontSee('Memoria')
            ->assertDontSee('Disco')
            ->assertDontSee('Batería')
            ->assertDontSee('Tensión de batería');
    }

    /**
     * `battery_voltage` la escriben siete endpoints IoT distintos
     * (`HardwareService::updateDeviceStatus()`), pero hasta ahora no tenía
     * ningún sitio donde verse en el panel.
     */
    #[Test]
    public function battery_voltage_shows_up_as_a_reading_card(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Rover Solar',
            'battery_voltage' => 12.6,
        ]);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->assertSuccessful()
            ->assertSee('Tensión de batería')
            ->assertSee('12.6');
    }

    #[Test]
    public function the_uptime_seconds_read_at_a_glance(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Rover',
            // 5 meses y pico en segundos.
            'uptime' => 14_212_800,
        ]);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->assertSee('5 meses');
    }

    #[Test]
    public function a_device_that_has_never_reported_does_not_show_the_section(): void
    {
        $device = HardwareDevice::create(['name' => 'Recién dado de alta']);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Estado del dispositivo');
    }

    /**
     * Lo importante: son lecturas de la API, no campos del formulario.
     */
    #[Test]
    public function saving_the_form_does_not_touch_the_readings(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Pico Display',
            'temp' => 21.5,
            'cpu' => 12,
            'ram' => 40,
            'disk' => 55,
            'uptime' => 14_212_800,
            'ip_local' => '192.168.1.50',
            'ip_public' => '80.30.20.10',
            'battery_voltage' => 12.6,
        ]);

        $before = $device->only(['temp', 'cpu', 'ram', 'disk', 'uptime', 'ip_local', 'ip_public', 'battery_voltage']);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->fillForm(['name_friendly' => 'El de la mesa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $device->refresh();

        $this->assertSame('El de la mesa', $device->name_friendly);
        $this->assertEquals(
            $before,
            $device->only(['temp', 'cpu', 'ram', 'disk', 'uptime', 'ip_local', 'ip_public', 'battery_voltage']),
        );
    }

    /**
     * `battery_nominal_voltage` es la tensión de diseño que declara el
     * fabricante, no una medida: se edita a mano desde el panel, junto a
     * `battery_nominal_capacity`.
     */
    #[Test]
    public function battery_nominal_voltage_is_editable_from_the_panel(): void
    {
        $device = HardwareDevice::create(['name' => 'Rover Solar']);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->fillForm(['battery_nominal_voltage' => 12.8])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(12.8, (float) $device->refresh()->battery_nominal_voltage);
    }

    /**
     * De las dos pestañas de abajo, la que se usa a diario es la de tokens.
     * Filament abre la primera del array.
     */
    #[Test]
    public function the_first_tab_is_the_iot_tokens_one(): void
    {
        $this->assertSame(
            TokensRelationManager::class,
            HardwareDeviceResource::getRelations()[0],
        );
    }
}
