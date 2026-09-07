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

    private HardwareDevice $controlador;

    private HardwareDevice $monitor;

    private EnergySystem $instalacion;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($this->user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $solar = HardwareType::firstOrCreate(
            ['slug' => 'controlador-solar'],
            ['name' => 'Controlador Solar'],
        );
        $otro = HardwareType::firstOrCreate(
            ['slug' => 'monitor-de-energia'],
            ['name' => 'Monitor de Energía'],
        );

        $this->instalacion = EnergySystem::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover',
            'slug' => Str::slug('Renogy Rover'),
        ]);

        $this->controlador = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
            'hardware_type_id' => $solar->id,
        ]);

        $this->monitor = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Raspberry Pi Pico W',
            'hardware_type_id' => $otro->id,
        ]);
    }

    private function elemento(HardwareDevice $medidor, string $role, int $canal = 0): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $medidor->id,
            'hardware_device_monitorized_id' => $medidor->id,
            'energy_system_id' => $this->instalacion->id,
            'role' => $role,
            'sensor_position' => $canal,
        ]);
    }

    #[Test]
    public function elementos_de_energia_deja_fuera_los_controladores_solares(): void
    {
        $delControlador = $this->elemento($this->controlador, HardwareEnergy::ROLE_GENERATOR);
        $delMonitor = $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->assertCanSeeTableRecords([$delMonitor])
            ->assertCanNotSeeTableRecords([$delControlador]);
    }

    #[Test]
    public function la_instalacion_solo_ensena_los_del_controlador(): void
    {
        $delControlador = $this->elemento($this->controlador, HardwareEnergy::ROLE_GENERATOR);
        $delMonitor = $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ElementsRelationManager::class, [
            'ownerRecord' => $this->instalacion,
            'pageClass' => EnergySystemResource\Pages\EditEnergySystem::class,
        ])
            ->assertCanSeeTableRecords([$delControlador])
            ->assertCanNotSeeTableRecords([$delMonitor]);
    }

    /**
     * Ningún elemento puede quedarse sin pantalla ni salir en las dos.
     */
    #[Test]
    public function los_dos_listados_no_se_solapan_y_no_dejan_nada_fuera(): void
    {
        $todos = collect([
            $this->elemento($this->controlador, HardwareEnergy::ROLE_GENERATOR),
            $this->elemento($this->controlador, HardwareEnergy::ROLE_LOAD),
            $this->elemento($this->controlador, HardwareEnergy::ROLE_BATTERY),
            $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD, canal: 1),
            $this->elemento($this->monitor, HardwareEnergy::ROLE_BATTERY, canal: 2),
        ]);

        $enElementos = HardwareEnergy::query()
            ->whereDoesntHave('hardwareDevice', fn ($q) => $q->whereHas('type', fn ($t) => $t->where('slug', 'controlador-solar')))
            ->pluck('id');

        $enInstalacion = HardwareEnergy::query()
            ->whereHas('hardwareDevice', fn ($q) => $q->whereHas('type', fn ($t) => $t->where('slug', 'controlador-solar')))
            ->pluck('id');

        $this->assertCount(0, $enElementos->intersect($enInstalacion), 'Ningún elemento debe salir en las dos pantallas.');
        $this->assertSame(
            $todos->pluck('id')->sort()->values()->all(),
            $enElementos->merge($enInstalacion)->sort()->values()->all(),
            'Ningún elemento debe quedarse sin pantalla.',
        );
    }

    /**
     * El listado se agrupa por el aparato que mide, que es lo único que no
     * cambia entre las filas de un mismo medidor.
     */
    #[Test]
    public function elementos_de_energia_se_agrupa_por_el_dispositivo_monitor(): void
    {
        $ventilador = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Ventilador']);

        $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD, canal: 1);

        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $ventilador->id,
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
    public function desde_un_elemento_se_pueden_crear_los_papeles_que_falten(): void
    {
        $consumo = $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD);

        $panel = Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $consumo,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ]);

        $panel->assertSuccessful()
            ->assertSee('crear_battery')
            ->assertSee('crear_generator')
            // De consumo caben más, así que el botón se queda.
            ->assertSee('crear_load');
    }

    #[Test]
    public function el_boton_del_papel_ya_creado_desaparece(): void
    {
        $consumo = $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD);
        $this->elemento($this->monitor, HardwareEnergy::ROLE_BATTERY, canal: 1);

        Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $consumo,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])->assertDontSee('crear_battery');
    }

    /**
     * Y lo que se crea desde ahí cuelga del mismo medidor, sin preguntarlo.
     */
    #[Test]
    public function el_papel_nuevo_cuelga_del_mismo_medidor(): void
    {
        $consumo = $this->elemento($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $consumo,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])
            ->callTableAction('crear_battery', data: [
                'hardware_device_monitorized_id' => $this->monitor->id,
                'sensor_position' => 5,
                'is_active' => true,
            ])
            ->assertHasNoTableActionErrors();

        $bateria = HardwareEnergy::where('role', HardwareEnergy::ROLE_BATTERY)->sole();

        $this->assertSame($this->monitor->id, $bateria->hardware_device_id);
    }
}
