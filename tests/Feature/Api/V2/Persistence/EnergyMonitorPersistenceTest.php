<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/hardware/energy-readings (D115).
 *
 * El monitor mide **varios canales a la vez** y manda uno por elemento, cada uno
 * con su `pos`. Esa `pos` casa con `hardware_energy.sensor_position`, que es lo
 * que dice **de qué aparato** es la lectura y si es generación o consumo.
 *
 * Las tres tablas en juego, que es lo que se confundía:
 *
 * | Tabla | Qué es |
 * |---|---|
 * | `hardware_energy` | **configuración**: qué mide este monitor y en qué canal |
 * | `hardware_power_loads` | lecturas de consumo |
 * | `hardware_power_generators` | lecturas de generación |
 *
 * v2 hacía `HardwareEnergy::create($data)`: por cada petición creaba una fila de
 * **configuración** basura y no guardaba ninguna medida, porque `cpu_avg` e
 * `intensity` ni siquiera son columnas suyas (**H1**, **R-3**).
 */
class EnergyMonitorPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    /** Monitor: el aparato que mide. */
    private HardwareDevice $monitor;

    /** Aparato medido en el canal 0. */
    private HardwareDevice $router;

    /** Aparato medido en el canal 1, que además genera. */
    private HardwareDevice $panel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->monitor = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Monitor de consumo',
        ]);
        $this->router = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Router',
        ]);
        $this->panel = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Panel solar',
        ]);

        // La configuración: qué mide el monitor en cada canal.
        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $this->router->id,
            'name' => 'Router principal',
            'role' => HardwareEnergy::ROLE_LOAD,
            'is_generator' => false,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
        ]);
        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $this->panel->id,
            'name' => 'Panel sur',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 1,
            'nominal_voltage' => 18.0,
        ]);
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return [
            'hardware_device_id' => $this->monitor->id,
            'temperature' => 41.5,
            'duration' => 60,
            'readings' => [
                ['pos' => 0, 'amperage' => 1.4, 'voltage' => 12.1],
                ['pos' => 1, 'amperage' => 3.2, 'voltage' => 18.6],
            ],
        ];
    }

    #[Test]
    public function each_channel_lands_in_its_own_table(): void
    {
        $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            $this->payload(),
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        )->assertStatus(201);

        $consumption = HardwarePowerLoad::query()->latest('id')->first();
        $generation = HardwarePowerGenerator::query()->latest('id')->first();

        $this->assertNotNull($consumption, 'El canal 0 (consumo) no dejó fila en `hardware_power_loads`.');
        $this->assertNotNull($generation, 'El canal 1 (generación) no dejó fila en `hardware_power_generators`.');

        $this->assertPersisted($consumption, [
            'hardware_device_id' => $this->router->id,
            'voltage' => 12.1,
            'amperage' => 1.4,
            'delta_seconds' => 60,
            'temperature' => 41.5,
            'voltage_source' => 'measured',
            'energy_source' => 'derived',
        ]);

        // W = V·A, y Wh = V·A·s/3600. Sin los segundos no habría energía.
        $this->assertEqualsWithDelta(12.1 * 1.4, (float) $consumption->power, 0.001);
        $this->assertEqualsWithDelta(12.1 * 1.4 * 60 / 3600, (float) $consumption->energy_wh, 0.001);
        $this->assertEqualsWithDelta(1.4 * 60 / 3600, (float) $consumption->energy_ah, 0.001);
    }

    /**
     * Sin tensión por elemento los vatios salen mal: se multiplicaba la
     * corriente de cada canal por **el único voltaje de la petición**. Un panel
     * de 18 V y un router de 12 V en la misma petición daban números sin
     * sentido. Cada canal trae la suya, y si no es creíble se usa la nominal del
     * elemento y se anota de dónde salió.
     */
    #[Test]
    public function an_impossible_voltage_is_replaced_by_the_nominal_one_with_a_warning(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            [
                'hardware_device_id' => $this->monitor->id,
                'duration' => 60,
                'readings' => [['pos' => 0, 'amperage' => 1.4, 'voltage' => 231.0]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        );

        $response->assertStatus(201);

        $consumption = HardwarePowerLoad::query()->latest('id')->first();

        $this->assertSame('nominal', $consumption->voltage_source);
        $this->assertEqualsWithDelta(12.0, (float) $consumption->voltage, 0.001);
        $this->assertNotEmpty($response->json('warnings'), 'Se sustituyó la tensión sin avisar.');
    }

    /**
     * Una corriente negativa es una avería de cableado, no un dato (D110). Se
     * guarda tal cual y se marca; lo que no hace es entrar en los totales.
     */
    #[Test]
    public function a_negative_current_is_flagged_but_not_discarded(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            [
                'hardware_device_id' => $this->monitor->id,
                'duration' => 60,
                'readings' => [['pos' => 0, 'amperage' => -1.4, 'voltage' => 12.1]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        );

        $response->assertStatus(201);

        $consumption = HardwarePowerLoad::query()->latest('id')->first();

        $this->assertNotNull($consumption, 'La lectura sospechosa se descartó en vez de marcarse.');
        $this->assertTrue((bool) $consumption->is_suspicious);
        $this->assertEqualsWithDelta(-1.4, (float) $consumption->amperage, 0.001);
        $this->assertNotEmpty($response->json('warnings'));
    }

    /**
     * La batería del propio monitor va en el dispositivo, no en las tablas de
     * energía (D108). Es lo que hacía el caso especial del dispositivo 14.
     */
    #[Test]
    public function the_device_battery_goes_on_the_device(): void
    {
        $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            array_merge($this->payload(), [
                'battery_voltage' => 3.92,
                'battery_percentage' => 78,
            ]),
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        )->assertStatus(201);

        $this->monitor->refresh();

        $this->assertEqualsWithDelta(3.92, (float) $this->monitor->battery_voltage, 0.001);
        $this->assertSame(78, (int) $this->monitor->battery_percentage);
        $this->assertNotNull($this->monitor->battery_read_at);
    }

    /**
     * La lectura es del aparato medido, no del monitor. Si se guardara contra el
     * monitor, los consumos de todos los canales se sumarían en el mismo sitio.
     */
    #[Test]
    public function the_reading_belongs_to_the_measured_device_not_the_monitor(): void
    {
        $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            $this->payload(),
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        )->assertStatus(201);

        $this->assertSame(
            0,
            HardwarePowerLoad::query()->where('hardware_device_id', $this->monitor->id)->count(),
            'La lectura se guardó contra el monitor en vez de contra el aparato medido.'
        );
        $this->assertSame(
            1,
            HardwarePowerLoad::query()->where('hardware_device_id', $this->router->id)->count()
        );
    }

    /**
     * `hardware_energy` es configuración. Una subida de lecturas no puede dar de
     * alta elementos nuevos: eso era exactamente el bug H1.
     */
    #[Test]
    public function uploading_readings_does_not_create_new_configuration(): void
    {
        $before = HardwareEnergy::query()->count();

        $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            $this->payload(),
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        )->assertStatus(201);

        $this->assertSame(
            $before,
            HardwareEnergy::query()->count(),
            'La petición creó filas en `hardware_energy`, que es la tabla de configuración (H1).'
        );
    }

    /**
     * Un canal cuya `pos` no está dada de alta no tiene aparato al que
     * atribuirse. Antes se aceptaba con 201 y el dato desaparecía.
     */
    #[Test]
    public function a_channel_without_a_registered_element_does_not_swallow_the_data(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            [
                'hardware_device_id' => $this->monitor->id,
                'duration' => 60,
                'readings' => [['pos' => 9, 'amperage' => 1.4, 'voltage' => 12.1]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        );

        $this->assertSame(
            422,
            $response->status(),
            'Una lectura que no casa con ningún elemento se aceptó con '.$response->status().' y se perdió.'
        );
        $this->assertSame(0, HardwarePowerLoad::query()->count());
    }

    #[Test]
    public function the_response_returns_the_stored_values(): void
    {
        $response = $this->postJson(
            $this->apiUrl('hardware/energy-readings'),
            $this->payload(),
            $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
        );

        $response->assertStatus(201);

        foreach ([
            'measured.voltage', 'measured.amperage', 'measured.delta_seconds', 'measured.temperature',
            'derived.power', 'derived.energy_wh', 'derived.energy_ah',
            'sources.energy', 'sources.voltage',
        ] as $field) {
            $this->assertNotNull(
                $response->json("data.0.{$field}"),
                "El Resource devuelve `{$field}` a NULL: el cliente no puede leer lo que acaba de escribir."
            );
        }
    }

    #[Test]
    public function cannot_write_on_someone_elses_device(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $foreign = HardwareDevice::create(['user_id' => $other->id, 'name' => 'Monitor ajeno']);

        $this->assertErrorResponse(
            $this->postJson(
                $this->apiUrl('hardware/energy-readings'),
                array_merge($this->payload(), ['hardware_device_id' => $foreign->id]),
                $this->moduleHeaders($this->user, TokenAbilities::HARDWARE_WRITE)
            ),
            422
        );
    }
}
