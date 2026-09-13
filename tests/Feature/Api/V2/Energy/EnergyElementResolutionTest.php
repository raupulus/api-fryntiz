<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Services\Hardware\HardwareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A qué elemento va cada lectura y de dónde sale su acumulado.
 */
class EnergyElementResolutionTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    private HardwareService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->device = HardwareDevice::create(['name' => 'Monitor']);
        $this->service = app(HardwareService::class);
    }

    private function elemento(string $role, int $channel = 0, array $extra = []): HardwareEnergy
    {
        return HardwareEnergy::create(array_merge([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => $role,
            'sensor_position' => $channel,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ], $extra));
    }

    #[Test]
    public function a_soft_deleted_element_does_not_break_the_ingestion(): void
    {
        $borrado = $this->elemento(HardwareEnergy::ROLE_GENERATOR);
        $borrado->delete();

        // El índice único no sabe de `deleted_at`: intentar crear otro igual
        // reventaba con QueryException y el dispositivo dejaba de poder subir.
        $resultado = $this->service->storeEnergyTelemetry($this->device->id, [
            'duration' => 60,
            'generator' => ['voltage' => 24.0, 'amperage' => 2.0],
        ]);

        $this->assertSame([], $resultado['readings'], 'La lectura no se guarda mientras el elemento siga borrado.');
        $this->assertStringContainsString('borrado', implode(' ', $resultado['warnings']));

        // Y no se ha colado ningún elemento nuevo duplicando el borrado.
        $this->assertSame(0, HardwareEnergy::query()->where('hardware_device_id', $this->device->id)->count());
    }

    #[Test]
    public function it_prefers_the_active_element_over_a_disabled_one_with_the_same_role(): void
    {
        // El índice único incluye el dispositivo monitorizado, así que un mismo
        // aparato puede tener dos generadores midiendo cosas distintas.
        $otroMedido = HardwareDevice::create(['name' => 'Panel viejo']);

        $desactivado = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $otroMedido->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => false,
        ]);

        $activo = $this->elemento(HardwareEnergy::ROLE_GENERATOR);

        $resultado = $this->service->storeEnergyTelemetry($this->device->id, [
            'duration' => 60,
            'generator' => ['voltage' => 24.0, 'amperage' => 2.0],
        ]);

        $this->assertCount(1, $resultado['readings']);
        $this->assertSame($activo->id, $resultado['readings'][0]->hardware_energy_id);
        $this->assertSame(0, HardwareEnergyReading::query()->where('hardware_energy_id', $desactivado->id)->count());
    }

    #[Test]
    public function an_auto_created_element_does_not_invent_a_nominal_voltage(): void
    {
        $resultado = $this->service->storeEnergyTelemetry($this->device->id, [
            'duration' => 60,
            'generator' => ['voltage' => 45.0, 'amperage' => 1.0],
        ]);

        $creado = HardwareEnergy::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('role', HardwareEnergy::ROLE_GENERATOR)
            ->first();

        $this->assertNotNull($creado);
        $this->assertNull(
            $creado->nominal_voltage,
            'Un pico de arranque no puede quedar fijado como tensión de referencia.'
        );

        // La lectura se guarda igual, con la tensión medida.
        $this->assertCount(1, $resultado['readings']);
        $this->assertFalse((bool) $resultado['readings'][0]->fresh()->is_suspicious);
        $this->assertSame(45.0, (float) $resultado['readings'][0]->voltage);
        $this->assertStringContainsString('dado de alta', implode(' ', $resultado['warnings']));
    }

    #[Test]
    public function a_session_fed_by_the_odometer_ignores_our_own_deltas(): void
    {
        $element = $this->elemento(HardwareEnergy::ROLE_GENERATOR);

        // Primera lectura con odómetro: fija la sesión.
        $this->service->storeEnergyTelemetry($this->device->id, [
            'duration' => 60,
            'generator' => ['voltage' => 24.0, 'amperage' => 2.0, 'historical_energy_wh' => 45000.0],
        ]);

        // Segunda lectura sin odómetro: antes le sumaba el delta encima del
        // total del aparato, y el `max()` hacía que no se pudiera deshacer.
        $this->service->storeEnergyTelemetry($this->device->id, [
            'duration' => 60,
            'generator' => ['voltage' => 24.0, 'amperage' => 2.0],
        ]);

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $historical->energy_wh_source);
        $this->assertSame(45000.0, (float) $historical->energy_wh);
    }

    #[Test]
    public function a_session_without_odometer_accumulates_our_deltas(): void
    {
        $element = $this->elemento(HardwareEnergy::ROLE_GENERATOR);

        foreach (range(1, 2) as $ignored) {
            $this->service->storeEnergyTelemetry($this->device->id, [
                'duration' => 3600,
                'generator' => ['voltage' => 24.0, 'amperage' => 1.0],
            ]);
        }

        $historical = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $element->id)
            ->first();

        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $historical->energy_wh_source);
        $this->assertEqualsWithDelta(48.0, (float) $historical->energy_wh, 0.001);
    }
}
