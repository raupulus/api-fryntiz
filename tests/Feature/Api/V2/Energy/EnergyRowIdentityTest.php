<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Qué identifica una fila de resumen: el elemento, no el dispositivo.
 *
 * `hardware_energy_today` y `hardware_energy_historical` llevan un
 * `hardware_device_id` que es una **desnormalización** de
 * `hardware_energy.hardware_device_id`: está para poder filtrar y pintar sin
 * unir tablas, no para identificar la fila. Los índices únicos lo dicen sin
 * ambigüedad:
 *
 * - `hardware_energy_today_energy_date_unique` → (`hardware_energy_id`, `date`)
 * - `hardware_energy_historical_energy_session_unique` → (`hardware_energy_id`, `session_index`)
 *
 * La ingesta y el cron buscaban además por dispositivo. Con eso, una fila cuyo
 * `hardware_device_id` no coincidiera con el del elemento quedaba invisible: no
 * se encontraba, se intentaba insertar otra, chocaba contra el índice único y
 * el aparato se comía un **500 en cada subida**, indefinidamente.
 *
 * No es hipotético. Pasa de dos maneras:
 *
 * 1. La migración del esquema antiguo atribuyó las filas de un elemento al
 *    dispositivo **monitorizado** en vez de al que mide (en la base real: el
 *    elemento 2, con 10 lecturas, un resumen del día y un acumulado).
 * 2. Reasignar un elemento a otro medidor desde el panel de Filament, que es
 *    un campo editable.
 *
 * Estos tests fijan que la fila se encuentre por elemento y que el dispositivo
 * se realinee solo con el del catálogo.
 */
class EnergyRowIdentityTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    /** El aparato que mide hoy. */
    private HardwareDevice $medidor;

    /** Otro aparato cualquiera: el que figura, mal, en las filas antiguas. */
    private HardwareDevice $ajeno;

    private HardwareEnergy $elemento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->medidor = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Medidor']);
        $this->ajeno = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Ajeno']);

        $this->elemento = HardwareEnergy::create([
            'hardware_device_id' => $this->medidor->id,
            'hardware_device_monitorized_id' => $this->ajeno->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    private function subir(): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->medidor->id,
                'duration' => 3600,
                'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0]]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    #[Test]
    public function a_daily_summary_left_on_another_device_does_not_break_the_upload(): void
    {
        HardwareEnergyToday::create([
            'hardware_device_id' => $this->ajeno->id,
            'hardware_energy_id' => $this->elemento->id,
            'date' => now('UTC')->format('Y-m-d'),
            'readings_count' => 3,
            'energy_wh' => 100.0,
            'energy_ah' => 8.0,
        ]);

        $this->subir()->assertStatus(201);
    }

    #[Test]
    public function that_upload_lands_on_the_existing_row_instead_of_opening_another(): void
    {
        HardwareEnergyToday::create([
            'hardware_device_id' => $this->ajeno->id,
            'hardware_energy_id' => $this->elemento->id,
            'date' => now('UTC')->format('Y-m-d'),
            'readings_count' => 3,
            'energy_wh' => 100.0,
            'energy_ah' => 8.0,
        ]);

        $this->subir()->assertStatus(201);

        $filas = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->elemento->id)
            ->get();

        $this->assertCount(1, $filas, 'Una fila por elemento y día, venga el dispositivo que venga en la que ya había.');
        $this->assertSame(4, $filas[0]->readings_count, 'Suma sobre lo que había, no empieza de cero.');
        $this->assertEqualsWithDelta(124.0, (float) $filas[0]->energy_wh, 0.001);
    }

    #[Test]
    public function the_daily_summary_realigns_with_the_device_that_measures(): void
    {
        HardwareEnergyToday::create([
            'hardware_device_id' => $this->ajeno->id,
            'hardware_energy_id' => $this->elemento->id,
            'date' => now('UTC')->format('Y-m-d'),
            'readings_count' => 3,
            'energy_wh' => 100.0,
            'energy_ah' => 8.0,
        ]);

        $this->subir()->assertStatus(201);

        $fila = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->elemento->id)
            ->firstOrFail();

        $this->assertSame(
            $this->medidor->id,
            $fila->hardware_device_id,
            'El dispositivo de la fila es el que el catálogo dice que mide el elemento.'
        );
    }

    #[Test]
    public function an_accumulator_left_on_another_device_does_not_open_a_second_session(): void
    {
        HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->ajeno->id,
            'hardware_energy_id' => $this->elemento->id,
            'session_index' => 1,
            'days_operating' => 10,
            'readings_count' => 50,
            'energy_wh' => 1000.0,
            'energy_ah' => 80.0,
        ]);

        $this->subir()->assertStatus(201);

        $sesiones = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->elemento->id)
            ->get();

        $this->assertCount(1, $sesiones, 'La sesión abierta se reconoce por elemento, no se duplica.');
        $this->assertEqualsWithDelta(1024.0, (float) $sesiones[0]->energy_wh, 0.001);
        $this->assertSame($this->medidor->id, $sesiones[0]->hardware_device_id);
    }

    #[Test]
    public function the_cron_also_finds_rows_left_on_another_device(): void
    {
        $ayer = now('UTC')->subDay()->format('Y-m-d');

        $this->travelTo(now('UTC')->subDay(), function (): void {
            $this->subir()->assertStatus(201);
        });

        // Se desalinea a mano lo que dejó la subida, como si viniera de la
        // migración del esquema antiguo.
        HardwareEnergyToday::query()->update(['hardware_device_id' => $this->ajeno->id]);
        HardwareEnergyHistorical::query()->update(['hardware_device_id' => $this->ajeno->id]);

        $this->artisan('energy:aggregate-daily', ['--date' => $ayer])->assertExitCode(0);

        $this->assertSame(
            1,
            HardwareEnergyToday::query()->where('hardware_energy_id', $this->elemento->id)->count(),
            'El cron actualiza el resumen que hay, no crea otro contra el índice único.'
        );

        $this->assertSame(
            1,
            HardwareEnergyHistorical::query()->where('hardware_energy_id', $this->elemento->id)->count(),
            'Ni abre una sesión nueva encima de la que ya existía.'
        );

        $this->assertSame(
            $this->medidor->id,
            HardwareEnergyHistorical::query()
                ->where('hardware_energy_id', $this->elemento->id)
                ->value('hardware_device_id')
        );
    }

    #[Test]
    public function moving_an_element_to_another_measuring_device_keeps_its_series(): void
    {
        $this->subir()->assertStatus(201);

        // Lo que haría un cambio de medidor en el panel de Filament.
        $otroMedidor = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Medidor nuevo']);
        $this->elemento->update(['hardware_device_id' => $otroMedidor->id]);

        $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $otroMedidor->id,
                'duration' => 3600,
                'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0]]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        )->assertStatus(201);

        $filas = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->elemento->id)
            ->get();

        $this->assertCount(1, $filas, 'Cambiar de medidor no parte el día del elemento en dos filas.');
        $this->assertSame(2, $filas[0]->readings_count);
        $this->assertSame($otroMedidor->id, $filas[0]->hardware_device_id);
    }
}
