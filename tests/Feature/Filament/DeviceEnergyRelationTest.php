<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers\EnergyRelationManager;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dar de alta la energía de un cacharro desde su propia ficha.
 *
 * Antes había que irse a otra pantalla, saber qué papel tocaba, acordarse del
 * canal y repetirlo por cada papel. Y nada impedía crear cuatro generadores del
 * mismo aparato.
 */
class DeviceEnergyRelationTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->device = HardwareDevice::create(['name' => 'Renogy Rover']);
    }

    /**
     * @return Testable
     */
    private function panel()
    {
        return Livewire::test(EnergyRelationManager::class, [
            'ownerRecord' => $this->device,
            'pageClass' => HardwareDeviceResource\Pages\EditHardwareDevice::class,
        ]);
    }

    #[Test]
    public function el_relation_manager_esta_en_la_ficha_del_dispositivo(): void
    {
        $this->assertContains(
            EnergyRelationManager::class,
            HardwareDeviceResource::getRelations(),
        );
    }

    #[Test]
    public function ofrece_un_boton_por_cada_papel(): void
    {
        $this->panel()
            ->assertTableHeaderActionsExistInOrder([
                'crear_generator',
                'crear_load',
                'crear_battery',
            ]);
    }

    /**
     * De generador hay uno, así que el botón se va cuando ya existe. El de
     * consumo se queda: un monitor mide tantas cargas como canales tenga.
     */
    #[Test]
    public function el_boton_desaparece_cuando_el_papel_ya_esta_ocupado(): void
    {
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
        ]);

        // El nombre de la acción viaja en el markup del botón, así que si no
        // está es que el botón no se ha pintado.
        $panel = $this->panel();

        $panel->assertDontSee('crear_generator');
        $panel->assertSee('crear_battery');
        $panel->assertSee('crear_load');
    }

    #[Test]
    public function el_de_consumo_se_queda_aunque_ya_haya_cargas(): void
    {
        foreach ([1, 2, 3] as $canal) {
            HardwareEnergy::create([
                'hardware_device_id' => $this->device->id,
                'hardware_device_monitorized_id' => $this->device->id,
                'role' => HardwareEnergy::ROLE_LOAD,
                'sensor_position' => $canal,
            ]);
        }

        $this->panel()->assertSee('crear_load');
    }

    /**
     * El papel lo pone el botón y el medido es él mismo: son las dos cosas que
     * hacían falta saber de memoria y ya no se preguntan.
     */
    #[Test]
    public function lo_creado_desde_la_ficha_se_mide_a_si_mismo(): void
    {
        $this->panel()
            ->callTableAction('crear_battery', data: [
                'sensor_position' => 0,
                'nominal_voltage' => 12.0,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $elemento = HardwareEnergy::where('hardware_device_id', $this->device->id)->sole();

        $this->assertSame(HardwareEnergy::ROLE_BATTERY, $elemento->role);
        $this->assertSame($this->device->id, $elemento->hardware_device_monitorized_id);
        $this->assertSame(12.0, (float) $elemento->nominal_voltage);
    }

    #[Test]
    public function la_tabla_ensena_los_papeles_que_ya_tiene(): void
    {
        foreach ([HardwareEnergy::ROLE_GENERATOR, HardwareEnergy::ROLE_LOAD] as $i => $rol) {
            HardwareEnergy::create([
                'hardware_device_id' => $this->device->id,
                'hardware_device_monitorized_id' => $this->device->id,
                'role' => $rol,
                'sensor_position' => $i,
            ]);
        }

        $this->panel()
            ->assertCanSeeTableRecords(HardwareEnergy::all())
            ->assertSee('Generador')
            ->assertSee('Consumo');
    }
}
