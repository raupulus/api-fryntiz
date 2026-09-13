<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ListEnergyDevices;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ManageEnergyDevice;
use App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers\BatteryRelationManager;
use App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers\GeneratorRelationManager;
use App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers\LoadRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La ficha energética de un aparato.
 *
 * Todo lo de un cacharro en una pantalla: arriba sus datos y su configuración,
 * editables; debajo una pestaña por papel con sus elementos.
 *
 * Las dos cosas que estaban mal y que estas pruebas sujetan:
 *
 * 1. La pantalla era de sólo lectura, y Filament pone los relation managers en
 *    sólo lectura cuando cuelgan de una `ViewRecord`. No se podía dar de alta
 *    ni un consumo más: los botones no se pintaban.
 * 2. El título ponía «Ver Aparato» y la primera sección «El aparato». Volver a
 *    una pestaña abierta no decía sobre qué cacharro estabas tocando.
 */
class EnergyDeviceViewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $controller;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($this->user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $tipo = HardwareType::firstOrCreate(
            ['slug' => HardwareType::SOLAR_CONTROLLER_SLUG],
            ['name' => 'Controlador Solar'],
        );

        $this->controller = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
            'hardware_type_id' => $tipo->id,
            'brand' => 'Renogy',
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
     * @param  class-string  $manager
     */
    private function pestana(string $manager): Testable
    {
        return Livewire::test($manager, [
            'ownerRecord' => $this->controller,
            'pageClass' => ManageEnergyDevice::class,
        ]);
    }

    // ── Que las páginas carguen de verdad ─────────────────────────────────

    /**
     * Una petición HTTP completa, con la página, sus pestañas y sus tablas
     * dentro. Probar los componentes por separado no vale para esto: un
     * relation manager roto no se nota hasta que se pinta con los demás.
     */
    #[Test]
    public function la_ficha_carga_entera_con_sus_tres_pestanas(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $this->element($this->controller, HardwareEnergy::ROLE_BATTERY);
        $this->element($this->controller, HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->get(EnergyDeviceResource::getUrl('edit', ['record' => $this->controller]))
            ->assertSuccessful()
            ->assertSee('Renogy Rover 20 LI')
            ->assertSee('Generadores')
            ->assertSee('Baterías')
            ->assertSee('Consumos')
            // Y ya no dice «Ver Aparato» ni «El aparato».
            ->assertDontSee('Ver Aparato')
            ->assertDontSee('El aparato');
    }

    #[Test]
    public function el_listado_carga_entero(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $this->get(EnergyDeviceResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Renogy Rover 20 LI');
    }

    // ── El listado ────────────────────────────────────────────────────────

    #[Test]
    public function solo_salen_los_aparatos_que_miden_energia(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $mudo = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Portátil']);

        Livewire::test(ListEnergyDevices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$this->controller])
            ->assertCanNotSeeTableRecords([$mudo]);
    }

    #[Test]
    public function los_aparatos_de_otros_no_salen(): void
    {
        $ajeno = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $suyo = HardwareDevice::create(['user_id' => $ajeno->id, 'name' => 'Ajeno']);
        $this->element($suyo, HardwareEnergy::ROLE_LOAD);

        $mio = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $miAparato = HardwareDevice::create(['user_id' => $mio->id, 'name' => 'Mío']);
        $this->element($miAparato, HardwareEnergy::ROLE_LOAD);

        $this->actingAs($mio);

        Livewire::test(ListEnergyDevices::class)
            ->assertCanSeeTableRecords([$miAparato])
            ->assertCanNotSeeTableRecords([$suyo]);
    }

    // ── La ficha ──────────────────────────────────────────────────────────

    #[Test]
    public function la_ficha_se_titula_con_el_nombre_del_aparato(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $pagina = Livewire::test(ManageEnergyDevice::class, ['record' => $this->controller->getKey()]);

        $pagina->assertSuccessful();

        $this->assertSame('Renogy Rover 20 LI', $pagina->instance()->getTitle());
        $this->assertStringContainsString('Controlador Solar', (string) $pagina->instance()->getSubheading());
        $this->assertStringContainsString('Renogy', (string) $pagina->instance()->getSubheading());
    }

    /**
     * Es lo que hace que la pantalla sirva para algo: se viene aquí a
     * configurar el aparato entero, no a mirarlo.
     */
    #[Test]
    public function la_ficha_deja_editar_los_datos_del_aparato(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(ManageEnergyDevice::class, ['record' => $this->controller->getKey()])
            ->fillForm(['name_friendly' => 'El del despacho'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('El del despacho', $this->controller->fresh()?->name_friendly);
    }

    #[Test]
    public function la_ficha_lleva_el_campo_de_imagen(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(ManageEnergyDevice::class, ['record' => $this->controller->getKey()])
            ->assertFormFieldExists('image_id');
    }

    // ── Una pestaña por papel ─────────────────────────────────────────────

    #[Test]
    public function hay_una_pestana_por_papel(): void
    {
        $this->assertSame(
            [GeneratorRelationManager::class, BatteryRelationManager::class, LoadRelationManager::class],
            EnergyDeviceResource::getRelations(),
        );
    }

    #[Test]
    public function cada_pestana_solo_ensena_los_suyos(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $banco = $this->element($this->controller, HardwareEnergy::ROLE_BATTERY);
        $consumo = $this->element($this->controller, HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->pestana(GeneratorRelationManager::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$panel])
            ->assertCanNotSeeTableRecords([$banco, $consumo]);

        $this->pestana(BatteryRelationManager::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$banco])
            ->assertCanNotSeeTableRecords([$panel, $consumo]);

        $this->pestana(LoadRelationManager::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$consumo])
            ->assertCanNotSeeTableRecords([$panel, $banco]);
    }

    // ── Dar de alta ───────────────────────────────────────────────────────

    /**
     * El fallo que se veía a simple vista: con la pantalla de sólo lectura no
     * había forma de añadir otro consumo.
     */
    #[Test]
    public function se_puede_anadir_otro_consumo(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_LOAD, channel: 0);

        $this->pestana(LoadRelationManager::class)
            ->callTableAction('create', data: ['sensor_position' => 1, 'is_active' => true])
            ->assertHasNoTableActionErrors();

        $this->assertSame(
            2,
            $this->controller->hardwareEnergy()->where('role', HardwareEnergy::ROLE_LOAD)->count(),
        );
    }

    #[Test]
    public function el_papel_lo_pone_la_pestana(): void
    {
        $this->pestana(BatteryRelationManager::class)
            ->callTableAction('create', data: ['sensor_position' => 0, 'is_active' => true])
            ->assertHasNoTableActionErrors();

        $banco = $this->controller->hardwareEnergy()->sole();

        $this->assertSame(HardwareEnergy::ROLE_BATTERY, $banco->role);
        $this->assertSame($this->controller->id, $banco->hardware_device_monitorized_id);
    }

    #[Test]
    public function del_generador_y_la_bateria_solo_cabe_uno(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $this->element($this->controller, HardwareEnergy::ROLE_BATTERY);

        $this->pestana(GeneratorRelationManager::class)->assertTableActionHidden('create');
        $this->pestana(BatteryRelationManager::class)->assertTableActionHidden('create');
        $this->pestana(LoadRelationManager::class)->assertTableActionVisible('create');
    }

    #[Test]
    public function desde_cada_papel_se_llega_a_su_telemetria(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $this->pestana(GeneratorRelationManager::class)->assertTableActionHasUrl(
            'telemetria',
            HardwareEnergyResource::getUrl('edit', ['record' => $panel]),
            record: $panel,
        );
    }

    #[Test]
    public function no_se_dan_de_alta_aparatos_desde_energia(): void
    {
        // Los aparatos son de Hardware; aquí sólo se gestiona su energía.
        $this->assertArrayNotHasKey('create', EnergyDeviceResource::getPages());
    }

    // ── El papel no se cambia después ─────────────────────────────────────

    #[Test]
    public function el_papel_no_se_puede_cambiar_al_editar(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->assertFormFieldDisabled('role');
    }

    #[Test]
    public function el_papel_si_se_elige_al_crear(): void
    {
        Livewire::test(CreateHardwareEnergy::class)->assertFormFieldEnabled('role');
    }

    #[Test]
    public function guardar_no_mueve_el_papel(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->fillForm(['role' => HardwareEnergy::ROLE_LOAD])
            ->call('save');

        $this->assertSame(HardwareEnergy::ROLE_GENERATOR, $panel->fresh()?->role);
    }
}
