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

        $tipo = HardwareType::firstOrCreate(
            ['slug' => HardwareType::SOLAR_CONTROLLER_SLUG],
            ['name' => 'Controlador Solar'],
        );

        $this->controller = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20 LI',
            'hardware_type_id' => $tipo->id,
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
    private function conTelemetria(HardwareEnergy $elemento): HardwareEnergy
    {
        HardwareEnergyReading::create([
            'hardware_device_id' => $elemento->hardware_device_id,
            'hardware_energy_id' => $elemento->id,
            'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
            'delta_seconds' => 300, 'energy_wh' => 8.0,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $elemento->hardware_device_id,
            'hardware_energy_id' => $elemento->id,
            'date' => now('UTC')->toDateString(),
            'readings_count' => 1,
            'energy_wh' => 743.0, 'energy_ah' => 31.0,
        ]);

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $elemento->hardware_device_id,
            'hardware_energy_id' => $elemento->id,
            'session_index' => 1,
            'energy_wh' => 524497.0, 'energy_ah' => 21854.0,
        ]);

        return $elemento;
    }

    private function ficha(?int $elemento = null): Testable
    {
        return Livewire::test(ManageEnergyDevice::class, array_filter([
            'record' => $this->controller->getKey(),
            'elementoId' => $elemento,
        ]));
    }

    // ── Que la página cargue de verdad ────────────────────────────────────

    /**
     * Una petición HTTP completa, con la página, sus pestañas y las tres tablas
     * de telemetría dentro.
     */
    #[Test]
    public function la_ficha_carga_entera_por_http(): void
    {
        $this->conTelemetria($this->element(HardwareEnergy::ROLE_GENERATOR));
        $this->conTelemetria($this->element(HardwareEnergy::ROLE_BATTERY));
        $this->conTelemetria($this->element(HardwareEnergy::ROLE_LOAD, channel: 1));

        $this->get(ManageEnergyDevice::getUrl(['record' => $this->controller]))
            ->assertSuccessful()
            // Quién es el aparato, arriba del todo.
            ->assertSee('Renogy Rover 20 LI')
            ->assertSee('Despacho')
            // Una pestaña por elemento.
            ->assertSee('Generador')
            ->assertSee('Batería')
            ->assertSee('Consumo')
            // Y sus tres tablas, sin salir de aquí.
            ->assertSee('Lecturas de telemetría')
            ->assertSee('Resúmenes diarios')
            ->assertSee('Histórico y sesiones')
            // Y el título es el nombre del cacharro, no «Ver Aparato».
            ->assertDontSee('Ver Aparato');
    }

    #[Test]
    public function el_listado_carga_entero(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->get(EnergyDeviceResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Renogy Rover 20 LI');
    }

    // ── Una pestaña por tupla ─────────────────────────────────────────────

    /**
     * Dos consumos son **dos** pestañas, no una con una tabla dentro.
     */
    #[Test]
    public function hay_una_pestana_por_fila_de_hardware_energy(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->element(HardwareEnergy::ROLE_BATTERY);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->assertCount(4, $this->ficha()->instance()->elementos());
    }

    #[Test]
    public function las_pestanas_van_en_el_orden_en_que_circula_la_energia(): void
    {
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);
        $this->element(HardwareEnergy::ROLE_BATTERY);
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->assertSame(
            [HardwareEnergy::ROLE_GENERATOR, HardwareEnergy::ROLE_BATTERY, HardwareEnergy::ROLE_LOAD],
            $this->ficha()->instance()->elementos()->pluck('role')->all(),
        );
    }

    /**
     * El canal sólo se nombra cuando hay más de un consumo: con uno solo es
     * ruido.
     */
    #[Test]
    public function la_pestana_nombra_el_canal_solo_cuando_hace_falta(): void
    {
        $unico = $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);

        $this->assertSame('Consumo', $this->ficha()->instance()->etiquetaDe($unico));

        $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);

        $this->assertSame('Consumo · canal 0', $this->ficha()->instance()->etiquetaDe($unico->refresh()));
    }

    #[Test]
    public function se_abre_por_el_primer_elemento_y_se_puede_cambiar(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $banco = $this->element(HardwareEnergy::ROLE_BATTERY);

        $ficha = $this->ficha();

        $this->assertSame($panel->id, $ficha->instance()->elementoActivo()?->id);

        $ficha->set('elementoId', $banco->id);

        $this->assertSame($banco->id, $ficha->instance()->elementoActivo()?->id);
    }

    /**
     * Un `?elemento=` a mano que apunte a otra cosa no rompe la página.
     */
    #[Test]
    public function un_elemento_de_otro_aparato_en_la_url_no_rompe_nada(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        $ajeno = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Otro']);
        $suyo = $this->element(HardwareEnergy::ROLE_LOAD, meter: $ajeno);

        $ficha = $this->ficha($suyo->id);

        $ficha->assertSuccessful();
        $this->assertSame($panel->id, $ficha->instance()->elementoActivo()?->id);
    }

    // ── Configurar sin salir de la página ─────────────────────────────────

    #[Test]
    public function la_configuracion_del_elemento_se_edita_aqui(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->ficha()
            ->fillForm(['nominal_voltage' => 24.0], 'configuracion')
            ->call('guardar');

        $this->assertEqualsWithDelta(24.0, (float) $panel->fresh()?->nominal_voltage, 0.001);
    }

    #[Test]
    public function cambiar_de_pestana_guarda_sobre_el_elemento_bueno(): void
    {
        // El esquema se cachea atado a un modelo: sin rehacerlo al cambiar de
        // pestaña, se guardaría sobre el elemento anterior.
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $banco = $this->element(HardwareEnergy::ROLE_BATTERY);

        $this->ficha()
            ->set('elementoId', $banco->id)
            ->fillForm(['nominal_voltage' => 48.0], 'configuracion')
            ->call('guardar');

        $this->assertEqualsWithDelta(48.0, (float) $banco->fresh()?->nominal_voltage, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $panel->fresh()?->nominal_voltage, 0.001, 'El de la otra pestaña no se toca.');
    }

    // ── Dar de alta ───────────────────────────────────────────────────────

    #[Test]
    public function se_puede_anadir_otro_consumo(): void
    {
        $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);

        $this->ficha()
            ->callAction('crear_load', data: ['sensor_position' => 1, 'is_active' => true])
            ->assertHasNoActionErrors();

        $this->assertSame(
            2,
            $this->controller->hardwareEnergy()->where('role', HardwareEnergy::ROLE_LOAD)->count(),
        );
    }

    /**
     * El requisito del módulo: un generador, una batería y tantos consumos como
     * canales.
     */
    #[Test]
    public function del_generador_y_la_bateria_solo_cabe_uno(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->element(HardwareEnergy::ROLE_BATTERY);

        $ficha = $this->ficha();

        $ficha->assertActionHidden('crear_generator');
        $ficha->assertActionHidden('crear_battery');
        $ficha->assertActionVisible('crear_load');
    }

    // ── El listado ────────────────────────────────────────────────────────

    #[Test]
    public function solo_salen_los_aparatos_que_miden_energia(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);

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
        $this->element(HardwareEnergy::ROLE_LOAD, meter: $suyo);

        $mio = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $miAparato = HardwareDevice::create(['user_id' => $mio->id, 'name' => 'Mío']);
        $this->element(HardwareEnergy::ROLE_LOAD, meter: $miAparato);

        $this->actingAs($mio);

        Livewire::test(ListEnergyDevices::class)
            ->assertCanSeeTableRecords([$miAparato])
            ->assertCanNotSeeTableRecords([$suyo]);
    }

    #[Test]
    public function no_se_dan_de_alta_aparatos_desde_energia(): void
    {
        // Los aparatos son de Hardware; aquí sólo se gestiona su energía.
        $this->assertArrayNotHasKey('create', EnergyDeviceResource::getPages());
    }

    // ── Lo que no se puede cambiar nunca ──────────────────────────────────

    #[Test]
    public function el_papel_no_se_puede_cambiar_al_editar(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->assertFormFieldDisabled('role');
    }

    /**
     * Cambiarlo dejaría lecturas y acumulados atribuidos a un medidor que nunca
     * los tomó, y en la telemetría no queda constancia de quién midió aparte de
     * esa columna.
     */
    #[Test]
    public function el_aparato_que_mide_tampoco_se_puede_cambiar(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->assertFormFieldDisabled('hardware_device_id');
    }

    #[Test]
    public function los_dos_si_se_eligen_al_crear(): void
    {
        Livewire::test(CreateHardwareEnergy::class)
            ->assertFormFieldEnabled('role')
            ->assertFormFieldEnabled('hardware_device_id');
    }

    #[Test]
    public function guardar_no_mueve_el_papel_ni_el_medidor(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $otro = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Otro medidor']);

        Livewire::test(EditHardwareEnergy::class, ['record' => $panel->getKey()])
            ->fillForm([
                'role' => HardwareEnergy::ROLE_LOAD,
                'hardware_device_id' => $otro->id,
            ])
            ->call('save');

        $fresco = $panel->fresh();

        $this->assertSame(HardwareEnergy::ROLE_GENERATOR, $fresco?->role);
        $this->assertSame($this->controller->id, $fresco?->hardware_device_id);
    }
}
