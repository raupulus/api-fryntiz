<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * El intervalo de muestreo cuando la subida no trae `duration`.
 *
 * `duration` es lo que convierte una potencia en energía:
 * `Wh = V · A · segundos / 3600`. Cuando no llega, el servidor tiene que
 * suponer algo, y suponía **60 segundos para todo el mundo**. Eso es una
 * mentira distinta en cada instalación: un nodo que sube cada diez minutos
 * registraba la sexta parte de la energía real, sin un aviso y sin forma de
 * notarlo hasta comparar con una pinza.
 *
 * Ahora el valor es de cada elemento (`default_interval_seconds`), con 60 de
 * partida para no cambiar lo que ya había, y editable desde el panel.
 *
 * **`duration` sigue mandando siempre que llegue**: sólo el aparato sabe
 * cuántos segundos pasaron de verdad cuando hubo un corte de red.
 */
class EnergySampleIntervalTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Nodo']);
    }

    private function elemento(string $role, ?int $intervalo = null): HardwareEnergy
    {
        return HardwareEnergy::create(array_filter([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => $role,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
            'default_interval_seconds' => $intervalo,
        ], static fn ($v) => $v !== null));
    }

    /**
     * @param  array<string, mixed>  $energy
     */
    private function subir(array $energy): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            ['hardware_device_id' => $this->device->id, 'energy' => $energy],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    private function lectura(HardwareEnergy $elemento): HardwareEnergyReading
    {
        return HardwareEnergyReading::query()
            ->where('hardware_energy_id', $elemento->id)
            ->firstOrFail();
    }

    #[Test]
    public function a_new_element_starts_at_sixty_seconds(): void
    {
        $elemento = $this->elemento(HardwareEnergy::ROLE_LOAD);

        $this->assertSame(60, $elemento->fresh()->default_interval_seconds);
        $this->assertSame(60, $elemento->defaultIntervalSeconds());
    }

    #[Test]
    public function an_upload_without_duration_uses_the_interval_of_its_element(): void
    {
        $elemento = $this->elemento(HardwareEnergy::ROLE_LOAD, 600);

        $this->subir(['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]]])
            ->assertStatus(201);

        $lectura = $this->lectura($elemento);

        $this->assertSame(600, $lectura->delta_seconds);
        // 12 W durante 600 s son 2 Wh, no los 0,2 que salían con los 60 fijos.
        $this->assertEqualsWithDelta(2.0, (float) $lectura->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(1.0 * 600 / 3600, (float) $lectura->energy_ah, 0.0001);
    }

    #[Test]
    public function the_duration_of_the_request_still_wins(): void
    {
        // Un corte de red: el aparato lleva 20 minutos acumulando y lo dice.
        $elemento = $this->elemento(HardwareEnergy::ROLE_LOAD, 600);

        $this->subir([
            'duration' => 1200,
            'loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]],
        ])->assertStatus(201);

        $lectura = $this->lectura($elemento);

        $this->assertSame(1200, $lectura->delta_seconds);
        $this->assertEqualsWithDelta(4.0, (float) $lectura->energy_wh, 0.0001);
    }

    #[Test]
    public function each_element_of_the_same_device_keeps_its_own_interval(): void
    {
        // Un controlador puede leer el panel cada minuto y la batería cada cinco.
        $generador = $this->elemento(HardwareEnergy::ROLE_GENERATOR, 60);
        $bateria = $this->elemento(HardwareEnergy::ROLE_BATTERY, 300);

        $this->subir([
            'generator' => ['voltage' => 12.0, 'amperage' => 1.0],
            'battery' => ['voltage' => 12.0, 'amperage' => 1.0],
        ])->assertStatus(201);

        $this->assertSame(60, $this->lectura($generador)->delta_seconds);
        $this->assertSame(300, $this->lectura($bateria)->delta_seconds);
    }

    #[Test]
    public function an_element_with_a_broken_interval_falls_back_to_sixty(): void
    {
        // Un 0 en la columna dividiría por cero al derivar; se ignora.
        $elemento = $this->elemento(HardwareEnergy::ROLE_LOAD);
        $elemento->forceFill(['default_interval_seconds' => 0])->saveQuietly();

        $this->subir(['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]]])
            ->assertStatus(201);

        $this->assertSame(HardwareEnergy::FALLBACK_INTERVAL_SECONDS, $this->lectura($elemento)->delta_seconds);
    }

    #[Test]
    public function an_element_created_on_the_fly_also_gets_an_interval(): void
    {
        // La ingesta da de alta el elemento que falta; tiene que nacer con uno.
        $this->subir(['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]]])
            ->assertStatus(201);

        $elemento = HardwareEnergy::query()->firstOrFail();

        $this->assertSame(60, $elemento->default_interval_seconds);
        $this->assertSame(60, HardwareEnergyReading::query()->firstOrFail()->delta_seconds);
    }
}
