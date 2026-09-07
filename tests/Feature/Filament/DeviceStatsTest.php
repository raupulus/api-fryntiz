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
    public function solo_se_pintan_las_lecturas_que_el_dispositivo_ha_reportado(): void
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
            ->assertDontSee('Batería');
    }

    #[Test]
    public function los_segundos_de_uptime_se_leen_de_un_vistazo(): void
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
    public function un_dispositivo_que_nunca_ha_reportado_no_ensena_la_seccion(): void
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
    public function guardar_la_ficha_no_toca_las_lecturas(): void
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
        ]);

        $antes = $device->only(['temp', 'cpu', 'ram', 'disk', 'uptime', 'ip_local', 'ip_public']);

        Livewire::test(EditHardwareDevice::class, ['record' => $device->getKey()])
            ->fillForm(['name_friendly' => 'El de la mesa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $device->refresh();

        $this->assertSame('El de la mesa', $device->name_friendly);
        $this->assertEquals(
            $antes,
            $device->only(['temp', 'cpu', 'ram', 'disk', 'uptime', 'ip_local', 'ip_public']),
        );
    }

    /**
     * De las dos pestañas de abajo, la que se usa a diario es la de tokens.
     * Filament abre la primera del array.
     */
    #[Test]
    public function la_primera_pestana_es_la_de_tokens_iot(): void
    {
        $this->assertSame(
            TokensRelationManager::class,
            HardwareDeviceResource::getRelations()[0],
        );
    }
}
