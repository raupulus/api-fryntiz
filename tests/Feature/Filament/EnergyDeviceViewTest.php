<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ListEnergyDevices;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ViewEnergyDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers\EnergyRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La entrada al módulo por el aparato, no por el papel suelto.
 *
 * Un controlador solar tiene tres papeles y mirarlos uno a uno obligaba a
 * volver al listado general entre medias. Aquí se entra por el aparato y dentro
 * están los tres, con un enlace a la telemetría de cada uno.
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

        $this->controller = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
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
    public function solo_salen_los_aparatos_que_miden_energia(): void
    {
        $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        $mudo = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Portátil']);

        Livewire::test(ListEnergyDevices::class)
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

    #[Test]
    public function la_ficha_del_aparato_ensena_todos_sus_papeles(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);
        $bateria = $this->element($this->controller, HardwareEnergy::ROLE_BATTERY);
        $consumo = $this->element($this->controller, HardwareEnergy::ROLE_LOAD, channel: 1);

        Livewire::test(ViewEnergyDevice::class, ['record' => $this->controller->getKey()])
            ->assertSuccessful();

        Livewire::test(EnergyRelationManager::class, [
            'ownerRecord' => $this->controller,
            'pageClass' => ViewEnergyDevice::class,
        ])->assertCanSeeTableRecords([$panel, $bateria, $consumo]);
    }

    #[Test]
    public function desde_cada_papel_se_llega_a_su_telemetria(): void
    {
        $panel = $this->element($this->controller, HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EnergyRelationManager::class, [
            'ownerRecord' => $this->controller,
            'pageClass' => ViewEnergyDevice::class,
        ])->assertTableActionHasUrl(
            'telemetria',
            HardwareEnergyResource::getUrl('edit', ['record' => $panel]),
            record: $panel,
        );
    }

    #[Test]
    public function no_se_dan_de_alta_aparatos_desde_energia(): void
    {
        // Los aparatos son de Hardware; aquí sólo se gestionan sus papeles.
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
        Livewire::test(CreateHardwareEnergy::class)
            ->assertFormFieldEnabled('role');
    }

    /**
     * Y el intento de cambiarlo a mano tampoco pasa: un campo deshabilitado no
     * se envía, así que el papel guardado sigue siendo el de antes.
     */
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
