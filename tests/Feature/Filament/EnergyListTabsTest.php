<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las pestañas del listado de elementos de energía.
 *
 * Son vistas del mismo listado, no compartimentos: nada desaparece de «Todos»
 * por estar en otra, y un elemento de un controlador solar sale tanto en
 * «Energía solar» como en la de su papel.
 */
class EnergyListTabsTest extends TestCase
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

        $solar = HardwareType::firstOrCreate(
            ['slug' => HardwareType::SOLAR_CONTROLLER_SLUG],
            ['name' => 'Controlador Solar'],
        );
        $otro = HardwareType::firstOrCreate(
            ['slug' => 'monitor-de-energia'],
            ['name' => 'Monitor de Energía'],
        );

        $this->controller = HardwareDevice::create([
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

    private function element(HardwareDevice $meter, string $role, int $channel = 0): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $meter->id,
            'hardware_device_monitorized_id' => $meter->id,
            'role' => $role,
            'sensor_position' => $channel,
        ]);
    }

    /**
     * @return array<string, Tab>
     */
    private function tabs(): array
    {
        return Livewire::test(ListHardwareEnergies::class)->instance()->getTabs();
    }

    #[Test]
    public function sin_elementos_solo_queda_la_pestana_de_todos(): void
    {
        $this->assertSame(['todos'], array_keys($this->tabs()));
    }

    #[Test]
    public function solo_aparece_la_pestana_del_papel_que_tiene_elementos(): void
    {
        $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        $claves = array_keys($this->tabs());

        $this->assertContains(HardwareEnergy::ROLE_LOAD, $claves);
        $this->assertNotContains(HardwareEnergy::ROLE_BATTERY, $claves);
        $this->assertNotContains(HardwareEnergy::ROLE_GENERATOR, $claves);
    }

    #[Test]
    public function la_pestana_solar_aparece_solo_con_controlador_solar(): void
    {
        $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        $this->assertNotContains('solar', array_keys($this->tabs()));

        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $this->assertContains('solar', array_keys($this->tabs()));
    }

    #[Test]
    public function la_pestana_solar_deja_fuera_lo_que_no_es_de_un_controlador(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $router = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->set('activeTab', 'solar')
            ->assertCanSeeTableRecords([$panel])
            ->assertCanNotSeeTableRecords([$router]);
    }

    /**
     * El elemento de un controlador solar no desaparece de la pestaña de su
     * papel: ahí es donde se le busca para comparar generadores entre sí.
     */
    #[Test]
    public function un_elemento_solar_tambien_sale_en_la_pestana_de_su_papel(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(ListHardwareEnergies::class)
            ->set('activeTab', HardwareEnergy::ROLE_GENERATOR)
            ->assertCanSeeTableRecords([$panel]);
    }

    #[Test]
    public function la_pestana_de_un_papel_deja_fuera_los_demas(): void
    {
        $bateria = $this->element($this->controller, HardwareEnergy::ROLE_BATTERY);
        $consumo = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->set('activeTab', HardwareEnergy::ROLE_BATTERY)
            ->assertCanSeeTableRecords([$bateria])
            ->assertCanNotSeeTableRecords([$consumo]);
    }

    #[Test]
    public function todos_sigue_ensenando_el_listado_entero(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $consumo = $this->element($this->monitor, HardwareEnergy::ROLE_LOAD);

        Livewire::test(ListHardwareEnergies::class)
            ->set('activeTab', 'todos')
            ->assertCanSeeTableRecords([$panel, $consumo]);
    }

    /**
     * Los números de las pestañas cuentan sobre la consulta del recurso, que
     * lleva el filtro por propietario. Si contaran sobre el modelo a pelo,
     * dirían más de lo que la tabla enseña.
     */
    #[Test]
    public function los_contadores_respetan_el_filtro_por_propietario(): void
    {
        $ajeno = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $suyo = HardwareDevice::create(['user_id' => $ajeno->id, 'name' => 'Ajeno']);
        $this->element($suyo, HardwareEnergy::ROLE_LOAD);

        $mio = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $miDispositivo = HardwareDevice::create(['user_id' => $mio->id, 'name' => 'Mío']);
        $this->element($miDispositivo, HardwareEnergy::ROLE_LOAD);

        $this->actingAs($mio);

        $tabs = $this->tabs();

        // `getBadge()` devuelve lo que se pinta, que es una cadena.
        $this->assertSame('1', $tabs['todos']->getBadge());
        $this->assertSame('1', $tabs[HardwareEnergy::ROLE_LOAD]->getBadge());
    }
}
