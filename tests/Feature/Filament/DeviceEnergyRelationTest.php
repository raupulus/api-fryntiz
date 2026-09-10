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
    public function the_relation_manager_is_on_the_device_page(): void
    {
        $this->assertContains(
            EnergyRelationManager::class,
            HardwareDeviceResource::getRelations(),
        );
    }

    #[Test]
    public function it_offers_a_button_for_each_role(): void
    {
        $this->panel()
            ->assertTableHeaderActionsExistInOrder([
                'create_generator',
                'create_load',
                'create_battery',
            ]);
    }

    /**
     * De generador hay uno, así que el botón se va cuando ya existe. El de
     * consumo se queda: un monitor mide tantas cargas como canales tenga.
     */
    #[Test]
    public function the_button_disappears_when_the_role_is_already_taken(): void
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

        $panel->assertDontSee('create_generator');
        $panel->assertSee('create_battery');
        $panel->assertSee('create_load');
    }

    #[Test]
    public function the_load_button_stays_even_when_loads_already_exist(): void
    {
        foreach ([1, 2, 3] as $channel) {
            HardwareEnergy::create([
                'hardware_device_id' => $this->device->id,
                'hardware_device_monitorized_id' => $this->device->id,
                'role' => HardwareEnergy::ROLE_LOAD,
                'sensor_position' => $channel,
            ]);
        }

        $this->panel()->assertSee('create_load');
    }

    /**
     * El papel lo pone el botón y el medido es él mismo: son las dos cosas que
     * hacían falta saber de memoria y ya no se preguntan.
     */
    #[Test]
    public function what_is_created_from_the_page_measures_itself(): void
    {
        $this->panel()
            ->callTableAction('create_battery', data: [
                'sensor_position' => 0,
                'nominal_voltage' => 12.0,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $energy = HardwareEnergy::where('hardware_device_id', $this->device->id)->sole();

        $this->assertSame(HardwareEnergy::ROLE_BATTERY, $energy->role);
        $this->assertSame($this->device->id, $energy->hardware_device_monitorized_id);
        $this->assertSame(12.0, (float) $energy->nominal_voltage);
    }

    #[Test]
    public function the_table_shows_the_roles_it_already_has(): void
    {
        foreach ([HardwareEnergy::ROLE_GENERATOR, HardwareEnergy::ROLE_LOAD] as $i => $role) {
            HardwareEnergy::create([
                'hardware_device_id' => $this->device->id,
                'hardware_device_monitorized_id' => $this->device->id,
                'role' => $role,
                'sensor_position' => $i,
            ]);
        }

        $this->panel()
            ->assertCanSeeTableRecords(HardwareEnergy::all())
            ->assertSee('Generador')
            ->assertSee('Consumo');
    }
}
