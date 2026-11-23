<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ListEnergyDevices;
use App\Filament\Admin\Resources\Energy\EnergyDevices\Pages\ManageEnergyDevice;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
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
 * La ficha energética de un aparato: **todo lo suyo en una sola página**.
 *
 * Arriba quién es. Debajo una pestaña por cada fila de `hardware_energy` —no
 * una por papel— y, dentro, su configuración editable y sus tres tablas de
 * telemetría, sin salir de la página.
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

        $type = HardwareType::firstOrCreate(
            ['slug' => HardwareType::SOLAR_CONTROLLER_SLUG],
            ['name' => 'Controlador Solar'],
        );

        $this->controller = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
            'hardware_type_id' => $type->id,
            'brand' => 'Renogy',
            'zone' => 'Despacho',
        ]);
    }

    private function element(string $role, int $channel = 0, ?HardwareDevice $meter = null): HardwareEnergy
    {
        $meter ??= $this->controller;

        return HardwareEnergy::create([
            'hardware_device_id' => $meter->id,
            'hardware_device_monitorized_id' => $meter->id,
            'role' => $role,
            'sensor_position' => $channel,
            'nominal_voltage' => 12.0,
        ]);
    }

    /**
     * Telemetría de verdad colgando de un elemento: lecturas, resumen del día y
     * acumulado. Sin esto las tablas se pintan vacías y no se prueba nada.
     */
    private function withTelemetry(HardwareEnergy $element): HardwareEnergy
    {
        HardwareEnergyReading::create([
            'hardware_device_id' => $element->hardware_device_id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
            'delta_seconds' => 300, 'energy_wh' => 8.0,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $element->hardware_device_id,
            'hardware_energy_id' => $element->id,
            'date' => now('UTC')->toDateString(),
            'readings_count' => 1,
            'energy_wh' => 743.0, 'energy_ah' => 31.0,
        ]);

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $element->hardware_device_id,
            'hardware_energy_id' => $element->id,
            'session_index' => 1,
            'energy_wh' => 524497.0, 'energy_ah' => 21854.0,
        ]);

        return $element;
    }

    private function page(?int $elementId = null): Testable
    {
        return Livewire::test(ManageEnergyDevice::class, array_filter([
            'record' => $this->controller->getKey(),
            'elementId' => $elementId,
        ]));
    }

    // ── Que la página cargue de verdad ────────────────────────────────────

    #[Test]
    public function device_page_loads_completely_over_http(): void
    {
        $this->withTelemetry($this->element(HardwareEnergy::ROLE_GENERATOR));
        $this->withTelemetry($this->element(HardwareEnergy::ROLE_BATTERY));
        $this->withTelemetry($this->element(HardwareEnergy::ROLE_LOAD, channel: 1));

        $this->get(ManageEnergyDevice::getUrl(['record' => $this->controller]))
            ->assertSuccessful()
            ->assertSee('Renogy Rover 20 LI')
            ->assertSee('Despacho')
            ->assertSee('Generador')
            ->assertSee('Batería')
            ->assertSee('Consumo')
            ->assertSee('Lecturas de telemetría')
            ->assertSee('Resúmenes diarios')
            ->assertSee('Histórico y sesiones')
            ->assertSee('Generador — potencia media por hora, últimos 7 días')
            ->assertSee('Batería — potencia media por hora, últimos 7 días')
            ->assertSee('Consumo — potencia media por hora, últimos 7 días')
            ->assertDontSee('Ver Aparato');
    }

    #[Test]
    public function device_list_loads_completely(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->get(EnergyDeviceResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Renogy Rover 20 LI');
    }

    // ── Una pestaña por tupla ─────────────────────────────────────────────

    #[Test]
    public function renders_one_tab_per_hardware_energy_row(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->element(HardwareEnergy::ROLE_BATTERY);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->assertCount(4, $this->page()->instance()->elements());
    }

    #[Test]
    public function tabs_follow_energy_flow_order(): void
    {
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);
        $this->element(HardwareEnergy::ROLE_BATTERY);
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->assertSame(
            [HardwareEnergy::ROLE_GENERATOR, HardwareEnergy::ROLE_BATTERY, HardwareEnergy::ROLE_LOAD],
            $this->page()->instance()->elements()->pluck('role')->all(),
        );
    }

    #[Test]
    public function tab_shows_channel_only_when_multiple_exist(): void
    {
        $single = $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);

        $this->assertSame('Consumo', $this->page()->instance()->labelFor($single));

        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->assertSame('Consumo · canal 0', $this->page()->instance()->labelFor($single->refresh()));
    }

    #[Test]
    public function opens_first_element_and_can_switch_tabs(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $battery = $this->element(HardwareEnergy::ROLE_BATTERY);

        $page = $this->page();

        $this->assertSame($panel->id, $page->instance()->activeElement()?->id);

        $page->set('elementId', $battery->id);

        $this->assertSame($battery->id, $page->instance()->activeElement()?->id);
    }

    #[Test]
    public function foreign_element_id_in_url_falls_back_safely(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        $other = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Otro']);
        $otherElement = $this->element(HardwareEnergy::ROLE_LOAD, meter: $other);

        $page = $this->page($otherElement->id);

        $page->assertSuccessful();
        $this->assertSame($panel->id, $page->instance()->activeElement()?->id);
    }

    // ── Configurar sin salir de la página ─────────────────────────────────

    #[Test]
    public function element_configuration_can_be_edited_inline(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->page()
            ->fillForm(['nominal_voltage' => 24.0], 'configuration')
            ->call('save');

        $this->assertEqualsWithDelta(24.0, (float) $panel->fresh()?->nominal_voltage, 0.001);
    }

    #[Test]
    public function switching_tabs_saves_changes_on_the_active_element(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $battery = $this->element(HardwareEnergy::ROLE_BATTERY);

        $this->page()
            ->set('elementId', $battery->id)
            ->fillForm(['nominal_voltage' => 48.0], 'configuration')
            ->call('save');

        $this->assertEqualsWithDelta(48.0, (float) $battery->fresh()?->nominal_voltage, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $panel->fresh()?->nominal_voltage, 0.001, 'El de la otra pestaña no se toca.');
    }

    // ── Dar de alta ───────────────────────────────────────────────────────

    #[Test]
    public function can_add_another_load_channel(): void
    {
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);

        $this->page()
            ->callAction('create_load', data: ['sensor_position' => 1, 'is_active' => true])
            ->assertHasNoActionErrors();

        $this->assertSame(
            2,
            $this->controller->hardwareEnergy()->where('role', HardwareEnergy::ROLE_LOAD)->count(),
        );
    }

    #[Test]
    public function generator_and_battery_are_limited_to_one_per_device(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->element(HardwareEnergy::ROLE_BATTERY);

        $page = $this->page();

        $page->assertActionHidden('create_generator');
        $page->assertActionHidden('create_battery');
        $page->assertActionVisible('create_load');
    }

    // ── El listado ────────────────────────────────────────────────────────

    #[Test]
    public function list_shows_only_devices_with_energy_channels(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $plain = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Portátil']);

        Livewire::test(ListEnergyDevices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$this->controller])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    #[Test]
    public function list_hides_devices_owned_by_other_users(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $otherDevice = HardwareDevice::create(['user_id' => $otherUser->id, 'name' => 'Ajeno']);
        $this->element(HardwareEnergy::ROLE_LOAD, meter: $otherDevice);

        $owner = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $myDevice = HardwareDevice::create(['user_id' => $owner->id, 'name' => 'Mío']);
        $this->element(HardwareEnergy::ROLE_LOAD, meter: $myDevice);

        $this->actingAs($owner);

        Livewire::test(ListEnergyDevices::class)
            ->assertCanSeeTableRecords([$myDevice])
            ->assertCanNotSeeTableRecords([$otherDevice]);
    }

    #[Test]
    public function energy_devices_cannot_be_created_from_energy_resource(): void
    {
        $this->assertArrayNotHasKey('create', EnergyDeviceResource::getPages());
    }

    // ── Lo que no se puede cambiar nunca ──────────────────────────────────

    #[Test]
    public function role_is_disabled_when_editing(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->assertFormFieldDisabled('role');
    }

    #[Test]
    public function meter_device_is_disabled_when_editing(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->assertFormFieldDisabled('hardware_device_id');
    }

    #[Test]
    public function role_and_meter_are_enabled_when_creating(): void
    {
        Livewire::test(CreateHardwareEnergy::class)
            ->assertFormFieldEnabled('role')
            ->assertFormFieldEnabled('hardware_device_id');
    }

    #[Test]
    public function saving_does_not_mutate_role_or_meter(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $other = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Otro medidor']);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->fillForm([
                'role' => HardwareEnergy::ROLE_LOAD,
                'hardware_device_id' => $other->id,
            ])
            ->call('save');

        $fresh = $panel->fresh();

        $this->assertSame(HardwareEnergy::ROLE_GENERATOR, $fresh?->role);
        $this->assertSame($this->controller->id, $fresh?->hardware_device_id);
    }
}
