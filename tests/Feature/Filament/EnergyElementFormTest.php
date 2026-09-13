<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

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
 * El formulario de alta de un elemento de energía.
 *
 * Dar de alta un elemento es el paso previo a que cualquier aparato pueda subir
 * nada: si la fila no está, la lectura se descarta o se auto-crea un elemento a
 * medio configurar. Por eso el formulario tiene que dejar claro qué pide y no
 * romperse a la cara del usuario.
 */
class EnergyElementFormTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $monitor;

    private HardwareDevice $medido;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->monitor = HardwareDevice::create(['name' => 'Raspberry con INA']);
        $this->medido = HardwareDevice::create(['name' => 'Router']);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $this->medido->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'is_active' => true,
            'nominal_voltage' => 12.0,
        ], $extra);
    }

    #[Test]
    public function a_repeated_channel_is_a_form_error_and_not_a_crash(): void
    {
        // El índice único de `hardware_energy` cubre medidor + medido + papel +
        // canal. Sin validación, repetirlo lanzaba una
        // `UniqueConstraintViolationException` y el panel enseñaba una pantalla
        // de error en vez de decir qué pasa.
        HardwareEnergy::create($this->datos());

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos())
            ->call('create')
            ->assertHasFormErrors(['sensor_position']);

        $this->assertSame(1, HardwareEnergy::query()->count());
    }

    #[Test]
    public function the_same_channel_on_another_measured_device_is_allowed(): void
    {
        // Un INA de tres canales mide tres aparatos distintos; lo que distingue
        // las filas es el canal, pero también el aparato medido.
        HardwareEnergy::create($this->datos());

        $otro = HardwareDevice::create(['name' => 'Servidor']);

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos([
                'hardware_device_monitorized_id' => $otro->id,
                'sensor_position' => 1,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, HardwareEnergy::query()->count());
    }

    #[Test]
    public function editing_an_element_without_touching_its_channel_does_not_collide_with_itself(): void
    {
        $elemento = HardwareEnergy::create($this->datos());

        Livewire::test(EditHardwareEnergy::class, ['record' => $elemento->getKey()])
            ->fillForm(['nominal_voltage' => 19.0])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(19.0, (float) $elemento->fresh()->nominal_voltage, 0.001);
    }

    #[Test]
    public function a_deleted_element_still_occupies_its_channel(): void
    {
        // El índice único no sabe de `deleted_at`, así que crear otro igual
        // choca igualmente. Mejor decirlo que reventar.
        $elemento = HardwareEnergy::create($this->datos());
        $elemento->delete();

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos())
            ->call('create')
            ->assertHasFormErrors(['sensor_position']);
    }

    #[Test]
    public function the_three_roles_can_be_registered_for_the_same_meter(): void
    {
        // El montaje del controlador solar: un generador, una batería y un
        // consumo, todos del mismo aparato y todos en el canal 0.
        foreach (HardwareEnergy::ROLES as $papel) {
            Livewire::test(CreateHardwareEnergy::class)
                ->fillForm($this->datos([
                    'hardware_device_monitorized_id' => $this->monitor->id,
                    'role' => $papel,
                    'sensor_position' => 0,
                ]))
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(3, HardwareEnergy::query()->count());
        $this->assertSame(
            ['battery', 'generator', 'load'],
            HardwareEnergy::query()->orderBy('role')->pluck('role')->all()
        );
    }

    #[Test]
    public function three_load_channels_can_be_registered_for_one_meter(): void
    {
        // El montaje del monitor multicanal: tres consumos, tres aparatos
        // medidos, tres canales.
        foreach ([0, 1, 2] as $canal) {
            $medido = HardwareDevice::create(['name' => "Aparato {$canal}"]);

            Livewire::test(CreateHardwareEnergy::class)
                ->fillForm($this->datos([
                    'hardware_device_monitorized_id' => $medido->id,
                    'sensor_position' => $canal,
                ]))
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(3, HardwareEnergy::query()->where('role', HardwareEnergy::ROLE_LOAD)->count());
        $this->assertSame([0, 1, 2], HardwareEnergy::query()->orderBy('sensor_position')->pluck('sensor_position')->all());
    }

    #[Test]
    public function the_channel_is_only_editable_on_a_load(): void
    {
        // La ingesta busca el generador y la batería por su papel, sin mirar el
        // canal. Dejarlo editable ahí sugiere que sirve para algo.
        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos(['role' => HardwareEnergy::ROLE_LOAD]))
            ->assertFormFieldIsEnabled('sensor_position');

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos(['role' => HardwareEnergy::ROLE_GENERATOR]))
            ->assertFormFieldIsDisabled('sensor_position');

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos(['role' => HardwareEnergy::ROLE_BATTERY]))
            ->assertFormFieldIsDisabled('sensor_position');
    }

    #[Test]
    public function a_generator_keeps_channel_zero_even_with_the_field_disabled(): void
    {
        // Deshabilitado pero no descartado: la columna es obligatoria.
        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos([
                'hardware_device_monitorized_id' => $this->monitor->id,
                'role' => HardwareEnergy::ROLE_GENERATOR,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, HardwareEnergy::query()->firstOrFail()->sensor_position);
    }

    #[Test]
    public function a_battery_asks_for_its_capacity_and_a_load_does_not(): void
    {
        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos([
                'hardware_device_monitorized_id' => $this->monitor->id,
                'role' => HardwareEnergy::ROLE_BATTERY,
            ]))
            ->assertFormFieldExists('capacity_ah');

        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos(['role' => HardwareEnergy::ROLE_LOAD]))
            ->assertFormFieldDoesNotExist('capacity_ah');
    }

    #[Test]
    public function a_battery_can_be_registered_with_its_charge_calibration(): void
    {
        // `voltage_min` y `voltage_max` son, en una batería, las tensiones a 0 %
        // y a 100 %: de ahí sale el porcentaje cuando el aparato no lo manda.
        Livewire::test(CreateHardwareEnergy::class)
            ->fillForm($this->datos([
                'hardware_device_monitorized_id' => $this->monitor->id,
                'role' => HardwareEnergy::ROLE_BATTERY,
                'nominal_voltage' => 12.0,
                'voltage_min' => 11.0,
                'voltage_max' => 14.6,
                'capacity_ah' => 250.0,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $bateria = HardwareEnergy::query()->firstOrFail();

        $this->assertEqualsWithDelta(11.0, (float) $bateria->voltage_min, 0.001);
        $this->assertEqualsWithDelta(14.6, (float) $bateria->voltage_max, 0.001);
        $this->assertEqualsWithDelta(3000.0, (float) $bateria->capacity_wh, 0.01, 'Wh = Ah · V nominal, calculado al vuelo.');
    }
}
