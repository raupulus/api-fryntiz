<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * El acumulado de por vida cuando el aparato trae su propio odómetro.
 *
 * Un odómetro es un total absoluto y lo que aporta al acumulado es **su avance
 * desde la última vez**, no su valor. De ahí salen las dos reglas que se
 * prueban aquí:
 *
 * 1. Adoptar un odómetro sobre un acumulado que ya existe **no suma nada** la
 *    primera vez: no hay forma de saber cuánto de lo que marca ya está contado.
 * 2. El reinicio se detecta comparando el odómetro **consigo mismo**, nunca
 *    contra nuestro acumulado.
 *
 * La segunda regla es la que faltaba, y costó una partición del histórico en
 * producción el 13/09/2026: el Rover lleva 524.497 Wh contados por nosotros
 * desde 2022 y su registro Modbus marca 41.206, así que la primera subida con
 * el contrato nuevo pareció un reinicio y abrió una sesión 2 en el panel, el
 * consumo y la batería.
 */
class EnergyOdometerTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $elemento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Rover']);
        $this->elemento = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $consumo
     */
    private function subir(array $consumo): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->device->id,
                'duration' => 600,
                'energy' => ['loads' => [$consumo + ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0]]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    /**
     * @return array<int, HardwareEnergyHistorical>
     */
    private function sesiones(): array
    {
        return HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->elemento->id)
            ->orderBy('session_index')
            ->get()
            ->all();
    }

    /**
     * Deja el acumulado como lo dejó el traspaso del esquema viejo: un total
     * nuestro, sumado de años de resúmenes diarios, sin odómetro del aparato.
     */
    private function acumuladoPrevio(float $wh): HardwareEnergyHistorical
    {
        return HardwareEnergyHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $this->elemento->id,
            'session_index' => 1,
            'days_operating' => 1600,
            'readings_count' => 0,
            'energy_wh' => $wh,
            'energy_ah' => 0,
        ]);
    }

    // ── Adopción ──────────────────────────────────────────────────────────

    #[Test]
    public function un_aparato_nuevo_estrena_el_acumulado_con_su_odometro(): void
    {
        $this->subir(['historical_energy_wh' => 5000.0])->assertStatus(201);

        $sesiones = $this->sesiones();

        $this->assertCount(1, $sesiones);
        $this->assertEqualsWithDelta(5000.0, (float) $sesiones[0]->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(5000.0, (float) $sesiones[0]->energy_wh_device_total, 0.0001);
    }

    /**
     * El caso del Rover: 524.497 Wh nuestros contra 41.206 suyos.
     */
    #[Test]
    public function un_odometro_menor_que_lo_acumulado_no_abre_sesion_ni_lo_pisa(): void
    {
        $this->acumuladoPrevio(524497.0);

        $this->subir(['historical_energy_wh' => 41206.0])->assertStatus(201);

        $sesiones = $this->sesiones();

        $this->assertCount(1, $sesiones, 'No hubo reinicio: el aparato nunca había mandado odómetro.');
        $this->assertEqualsWithDelta(524497.0, (float) $sesiones[0]->energy_wh, 0.0001, 'Ni se pisa…');
        $this->assertEqualsWithDelta(41206.0, (float) $sesiones[0]->energy_wh_device_total, 0.0001, '…ni se olvida el punto de partida.');
    }

    #[Test]
    public function desde_la_adopcion_se_suma_solo_lo_que_avanza(): void
    {
        $this->acumuladoPrevio(524497.0);

        foreach ([41206.0, 41220.0, 41234.0] as $odometro) {
            $this->subir(['historical_energy_wh' => $odometro])->assertStatus(201);
        }

        $sesiones = $this->sesiones();

        // 524.497 + (41.220 − 41.206) + (41.234 − 41.220) = 524.525
        $this->assertEqualsWithDelta(524525.0, (float) $sesiones[0]->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(41234.0, (float) $sesiones[0]->energy_wh_device_total, 0.0001);
    }

    #[Test]
    public function el_odometro_no_se_suma_entero_cada_vez(): void
    {
        // Lo que pasaba cuando el acumulado guardaba el odómetro con max():
        // tres subidas del mismo total dejaban el total, no el triple. Ahora
        // tampoco, pero por la razón buena: el avance es cero.
        foreach ([5000.0, 5000.0, 5000.0] as $odometro) {
            $this->subir(['historical_energy_wh' => $odometro])->assertStatus(201);
        }

        $this->assertEqualsWithDelta(5000.0, (float) $this->sesiones()[0]->energy_wh, 0.0001);
    }

    // ── Retrocesos ────────────────────────────────────────────────────────

    #[Test]
    public function un_retroceso_pequeno_ni_suma_ni_resta(): void
    {
        $this->subir(['historical_energy_wh' => 5000.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 4900.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 5010.0])->assertStatus(201);

        $sesiones = $this->sesiones();

        $this->assertCount(1, $sesiones);
        // La referencia no retrocedió a 4.900, así que el avance bueno es
        // 5.010 − 5.000 = 10, no 110.
        $this->assertEqualsWithDelta(5010.0, (float) $sesiones[0]->energy_wh, 0.0001);
    }

    // ── Reinicio de verdad ────────────────────────────────────────────────

    #[Test]
    public function un_odometro_que_se_desploma_abre_otra_sesion(): void
    {
        $this->subir(['historical_energy_wh' => 5000.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 120.0])->assertStatus(201);

        $sesiones = $this->sesiones();

        $this->assertCount(2, $sesiones);
        $this->assertEqualsWithDelta(5000.0, (float) $sesiones[0]->energy_wh, 0.0001, 'Lo de antes se conserva.');
        $this->assertEqualsWithDelta(120.0, (float) $sesiones[1]->energy_wh, 0.0001, 'Lo nuevo empieza en lo que marca.');
    }

    #[Test]
    public function tras_el_reinicio_se_vuelve_a_contar_avances(): void
    {
        $this->subir(['historical_energy_wh' => 5000.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 120.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 180.0])->assertStatus(201);

        $sesiones = $this->sesiones();

        $this->assertCount(2, $sesiones, 'Un reinicio, una sesión nueva. No una por lectura.');
        $this->assertEqualsWithDelta(180.0, (float) $sesiones[1]->energy_wh, 0.0001);
    }

    #[Test]
    public function un_odometro_pequeno_no_se_juzga(): void
    {
        // Por debajo del suelo, la proporción no dice nada: 40 → 15 es ruido
        // de un aparato recién estrenado, no un reinicio.
        $this->subir(['historical_energy_wh' => 40.0])->assertStatus(201);
        $this->subir(['historical_energy_wh' => 15.0])->assertStatus(201);

        $this->assertCount(1, $this->sesiones());
    }

    // ── Magnitud a magnitud ───────────────────────────────────────────────

    #[Test]
    public function cada_magnitud_lleva_su_propio_odometro(): void
    {
        // El Rover manda amperios-hora de batería pero no vatios-hora: el
        // avance de una no puede depender de la otra.
        $this->subir(['historical_energy_ah' => 12000.0])->assertStatus(201);
        $this->subir(['historical_energy_ah' => 12010.0])->assertStatus(201);

        $sesion = $this->sesiones()[0];

        $this->assertEqualsWithDelta(12010.0, (float) $sesion->energy_ah, 0.0001);
        $this->assertNull($sesion->energy_wh_device_total, 'De vatios-hora nunca mandó odómetro.');
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $sesion->energy_wh_source);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $sesion->energy_ah_source);
    }

    #[Test]
    public function sin_odometro_se_siguen_sumando_nuestros_deltas(): void
    {
        // 12 V × 2 A × 600 s = 4 Wh por lectura.
        $this->subir([])->assertStatus(201);
        $this->subir([])->assertStatus(201);

        $sesion = $this->sesiones()[0];

        $this->assertEqualsWithDelta(8.0, (float) $sesion->energy_wh, 0.0001);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $sesion->energy_wh_source);
    }
}
