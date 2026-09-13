<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\HistoricalRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\ReadingsRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\RolesRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers\TodayRelationManager;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La telemetría de energía **sólo entra por la API**.
 *
 * Las tres tablas —lecturas, resumen del día y sesión histórica— son un reflejo
 * de lo que han mandado los aparatos. Una fila escrita a mano desde el panel no
 * tiene aparato detrás: no cuadra con ninguna lectura, el cierre nocturno la
 * recalcula o la respeta según columnas que nadie ha rellenado bien, y a partir
 * de ahí no hay forma de saber qué número es real.
 *
 * Borrar sí hace falta —para limpiar una serie corrupta—, pero es de
 * administradores y con confirmación: el dato no se puede volver a pedir, el
 * aparato ya lo mandó y no lo reenvía.
 *
 * Las tres pantallas del catálogo (`RolesRelationManager` y la ficha del
 * dispositivo) **sí** dejan crear: ahí se dan de alta los elementos, que es
 * configuración, no telemetría.
 */
class EnergyTelemetryReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private HardwareEnergy $elemento;

    /**
     * La clave de la fila de cada tabla, para apuntar a ella en las acciones.
     *
     * @var array<class-string, int>
     */
    private array $filas = [];

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $device = HardwareDevice::create(['name' => 'Renogy Rover']);

        $this->elemento = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->filas[ReadingsRelationManager::class] = HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $this->elemento->id,
            'voltage' => 24.0, 'amperage' => 4.0, 'power' => 96.0,
            'delta_seconds' => 60, 'energy_wh' => 1.6, 'energy_ah' => 0.066,
            'energy_source' => 'derived', 'voltage_source' => 'measured',
        ])->getKey();

        $this->filas[TodayRelationManager::class] = HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $this->elemento->id,
            'date' => now('UTC')->toDateString(),
            'readings_count' => 1, 'energy_wh' => 1.6, 'energy_ah' => 0.066,
        ])->getKey();

        $this->filas[HistoricalRelationManager::class] = HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $this->elemento->id,
            'session_index' => 1, 'days_operating' => 1,
            'readings_count' => 1, 'energy_wh' => 1.6, 'energy_ah' => 0.066,
        ])->getKey();
    }

    private function entrarComo(bool $administrador): void
    {
        $this->actingAs(User::factory()->create([
            'role_id' => $administrador ? 1 : 3,
            'is_active' => true,
        ]));
    }

    /**
     * @return Testable
     */
    private function panel(string $relationManager)
    {
        return Livewire::test($relationManager, [
            'ownerRecord' => $this->elemento,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ]);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function relationManagers(): array
    {
        return [
            'lecturas' => [ReadingsRelationManager::class, 'lectura'],
            'resúmenes del día' => [TodayRelationManager::class, 'resumen del día'],
            'sesiones históricas' => [HistoricalRelationManager::class, 'sesión histórica'],
        ];
    }

    #[Test]
    public function no_energy_screen_lets_you_create_telemetry_by_hand(): void
    {
        $this->entrarComo(administrador: true);

        foreach (self::relationManagers() as [$relationManager, $que]) {
            $this->panel($relationManager)->assertTableActionDoesNotExist('create');
        }
    }

    #[Test]
    public function an_administrator_can_delete_telemetry(): void
    {
        $this->entrarComo(administrador: true);

        foreach (self::relationManagers() as [$relationManager, $que]) {
            $this->panel($relationManager)
                ->assertTableActionVisible('delete', record: $this->filas[$relationManager]);
        }
    }

    #[Test]
    public function deleting_telemetry_asks_for_confirmation(): void
    {
        $this->entrarComo(administrador: true);

        foreach (self::relationManagers() as [$relationManager, $que]) {
            $accion = $this->panel($relationManager)
                ->instance()
                ->getTable()
                ->getAction('delete');

            $this->assertNotNull($accion, "No hay acción de borrado en {$relationManager}.");

            $this->assertTrue(
                $accion->isConfirmationRequired(),
                "Borrar una {$que} tiene que pedir confirmación."
            );
            $this->assertSame('Borrar esta '.$que, $accion->getModalHeading());
        }
    }

    #[Test]
    public function someone_who_is_not_an_administrator_cannot_delete_telemetry(): void
    {
        $this->entrarComo(administrador: false);

        foreach (self::relationManagers() as [$relationManager, $que]) {
            $this->panel($relationManager)
                ->assertTableActionHidden('delete', record: $this->filas[$relationManager]);
        }
    }

    #[Test]
    public function the_energy_element_catalogue_does_let_you_create(): void
    {
        // Dar de alta un elemento es configurar la instalación, no inventarse
        // telemetría: eso se hace desde el panel y tiene que seguir pudiéndose.
        $this->entrarComo(administrador: true);

        Livewire::test(RolesRelationManager::class, [
            'ownerRecord' => $this->elemento,
            'pageClass' => HardwareEnergyResource\Pages\EditHardwareEnergy::class,
        ])->assertTableActionExists('create_load');
    }
}
