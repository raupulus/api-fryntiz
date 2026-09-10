<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\EnergySystems\EnergySystemResource;
use App\Filament\Admin\Resources\Hardware\EnergySystems\RelationManagers\ElementsRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\RolesRelationManager;
use App\Models\Hardware\EnergySystem;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cada elemento en su pantalla, y sólo en una.
 *
 * Los de un controlador solar se gestionan desde su instalación; los demás, en
 * «Elementos de Energía». Si un elemento saliera en las dos, editarlo en una y
 * mirarlo en la otra daría respuestas distintas.
 */
class EnergyListsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $controller;

    private HardwareDevice $monitor;

    private EnergySystem $installation;

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

        $this->installation = EnergySystem::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover',
            'slug' => Str::slug('Renogy Rover'),
        ]);

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
            'energy_system_id' => $this->installation->id,
            'role' => $role,
            'sensor_position' => $channel,
        ]);
    }

    #[Test]
    public function energy_elements_excludes_solar_controllers(): void
    {
        $controllerElement = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $monitorElement = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->assertCanSeeTableRecords([$monitorElement])
            ->assertCanNotSeeTableRecords([$controllerElement]);
    }

    #[Test]
    public function the_installation_only_shows_the_controllers_elements(): void
    {
        $controllerElement = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $monitorElement = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ElementsRelationManager::class, [
            'ownerRecord' => $this->installation,
            'pageClass' => EnergySystemResource\Pages\EditEnergySystem::class,
        ])
            ->assertCanSeeTableRecords([$controllerElement])
            ->assertCanNotSeeTableRecords([$monitorElement]);
    }

    /**
     * Ningún elemento puede quedarse sin pantalla ni salir en las dos.
     */
    #[Test]
    public function the_two_lists_do_not_overlap_and_leave_nothing_out(): void
    {
        $all = collect([
            $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR),
            $this->element($this->controller, HardwareEnergy::ROLE_LOAD),
            $this->element($this->controller, HardwareEnergy::ROLE_BATTERY),
            $this->element($this->monitor, HardwareEnergy::ROLE_LOAD, channel: 1),
            $this->element($this->monitor, HardwareEnergy::ROLE_BATTERY, channel: 2),
        ]);

        $inElements = HardwareEnergy::query()
            ->whereDoesntHave('hardwareDevice', fn ($q) => $q->whereHas('type', fn ($t) => $t->where('slug', 'controlador-solar')))
            ->pluck('id');

        $inInstallation = HardwareEnergy::query()
            ->whereHas('hardwareDevice', fn ($q) => $q->whereHas('type', fn ($t) => $t->where('slug', 'controlador-solar')))
            ->pluck('id');

        $this->assertCount(0, $inElements->intersect($inInstallation), 'Ningún elemento debe salir en las dos pantallas.');
        $this->assertSame(
            $all->pluck('id')->sort()->values()->all(),
            $inElements->merge($inInstallation)->sort()->values()->all(),
            'Ningún elemento debe quedarse sin pantalla.',
        );
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
     * Lo que se reportó: estando en «Editar Elemento Energético» no había forma
     * de crear el papel que falta —la batería de un controlador, por ejemplo—.
     * Las dos pestañas de abajo son las **lecturas**, no los papeles, y eso
     * hacía pensar que la batería no se podía crear.
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
            // De consumo caben más, así que el botón se queda.
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
     * Y lo que se crea desde ahí cuelga del mismo medidor, sin preguntarlo.
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
