<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\RolesRelationManager;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de listado y gestión de roles unificados en Filament Admin para HardwareEnergy.
 *
 * Todos los elementos de energía (tanto monitores normales como controladores solares)
 * se gestionan en una única lista unificada agrupada por dispositivo medidor.
 */
class EnergyListsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $controller;

    private HardwareDevice $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($this->user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $solarType = HardwareType::firstOrCreate(
            ['slug' => 'controlador-solar'],
            ['name' => 'Controlador Solar'],
        );
        $otherType = HardwareType::firstOrCreate(
            ['slug' => 'monitor-de-energia'],
            ['name' => 'Monitor de Energía'],
        );

        $this->controller = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
            'hardware_type_id' => $solarType->id,
        ]);

        $this->monitor = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Raspberry Pi Pico W',
            'hardware_type_id' => $otherType->id,
        ]);
    }

    private function element(HardwareDevice $meter, string $role, int $channel = 0): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $meter->id,
            'hardware_device_monitorized_id' => $meter->id,
            'role' => $role,
            'sensor_position' => $channel,
        ]);
    }

    #[Test]
    public function all_energy_elements_are_listed_including_solar_controllers(): void
    {
        $controllerElement = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $monitorElement = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->assertCanSeeTableRecords([$monitorElement, $controllerElement]);
    }

    #[Test]
    public function elements_of_other_users_are_scoped_out(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $otherDevice = HardwareDevice::create([
            'user_id' => $otherUser->id,
            'name' => 'Dispositivo Ajeno',
        ]);
        $otherElement = $this->element($otherDevice, HardwareEnergy::ROLE_LOAD);

        // Como SuperAdmin ve todo; cambiamos a usuario normal para verificar ScopesToOwner
        $normalUser = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $myDevice = HardwareDevice::create([
            'user_id' => $normalUser->id,
            'name' => 'Mi Dispositivo',
        ]);
        $normalElement = $this->element($myDevice, HardwareEnergy::ROLE_LOAD);

        $this->actingAs($normalUser);

        Livewire::test(ListHardwareEnergies::class)
            ->assertCanSeeTableRecords([$normalElement])
            ->assertCanNotSeeTableRecords([$otherElement]);
    }

    /**
     * El listado se agrupa por el aparato que mide, que es lo único que no
     * cambia entre las filas de un mismo medidor.
     */
    #[Test]
    public function energy_elements_are_grouped_by_the_monitoring_device(): void
    {
        $fan = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Ventilador']);

        $this->element($this->monitor, HardwareEnergy::ROLE_LOAD, channel: 1);

        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $fan->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 2,
        ]);

        Livewire::test(ListHardwareEnergies::class)
            ->assertSuccessful()
            ->assertSee('Raspberry Pi Pico W')
            ->assertSee('Ventilador');
    }

    /**
     * Estando en «Editar Elemento Energético», RolesRelationManager permite
     * crear los roles restantes que aún no están asignados en el mismo medidor.
     */
    #[Test]
    public function missing_roles_can_be_created_from_an_element(): void
    {
        $load = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        $panel = Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $load,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ]);

        $panel->assertSuccessful()
            ->assertSee('create_battery')
            ->assertSee('create_generator')
            // De consumo caben más, así que el botón se mantiene.
            ->assertSee('create_load');
    }

    #[Test]
    public function the_button_for_an_already_created_role_disappears(): void
    {
        $load = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);
        $this->element($this->monitor, HardwareEnergy::ROLE_BATTERY, channel: 1);

        Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $load,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])->assertDontSee('create_battery');
    }

    /**
     * El nuevo rol creado desde el relation manager cuelga automáticamente del mismo medidor.
     */
    #[Test]
    public function the_new_role_hangs_off_the_same_meter(): void
    {
        $load = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $load,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])
            ->callTableAction('create_battery', data: [
                'hardware_device_monitorized_id' => $this->monitor->id,
                'sensor_position' => 5,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $battery = HardwareEnergy::where('role', HardwareEnergy::ROLE_BATTERY)->sole();

        $this->assertSame($this->monitor->id, $battery->hardware_device_id);
    }
}
