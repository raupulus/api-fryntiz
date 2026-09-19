<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * La energía de cada lectura sale del **total de vida** del aparato cuando lo
 * manda: total de ahora − total anterior guardado.
 *
 * El avance que calcula el aparato entre dos subidas se pierde cuando se
 * reinicia; su total de vida no. El 16/09/2026 la Pico del Rover estuvo 17 h
 * caída y el día se quedó sin 180 Wh generados que el total sí había contado.
 *
 * Un test por escenario; un aparato sin total de vida sigue como siempre.
 */
class EnergyIntervalFromOdometerTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $consumo;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-20 10:00:00');

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Rover']);
        $this->consumo = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Sube una lectura del consumo cinco minutos después de la anterior.
     *
     * 12 V × 2 A durante 300 s son 2 Wh si hubiera que calcularlos.
     *
     * @param  array<string, mixed>  $consumo
     */
    private function subir(array $consumo, int $minutosDespues = 5): TestResponse
    {
        Carbon::setTestNow(now()->addMinutes($minutosDespues));

        return $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->device->id,
                'duration' => 300,
                'energy' => ['loads' => [$consumo + ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0]]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    private function ultimaLectura(): HardwareEnergyReading
    {
        return HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->consumo->id)
            ->latest('id')
            ->firstOrFail();
    }

    private function whDelDia(): float
    {
        return (float) HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->consumo->id)
            ->sum('energy_wh');
    }

    private function referenciaWh(): ?float
    {
        $valor = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->consumo->id)
            ->orderByDesc('session_index')
            ->value('energy_wh_device_total');

        return $valor !== null ? (float) $valor : null;
    }

    /** 1 · Funcionamiento normal: la resta da lo mismo que el avance. */
    #[Test]
    public function normal_operation_gives_the_same_as_the_device_step(): void
    {
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1000])->assertStatus(201);
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1013])->assertStatus(201);

        $this->assertEqualsWithDelta(13.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertSame('device', $this->ultimaLectura()->energy_source);
        $this->assertEqualsWithDelta(26.0, $this->whDelDia(), 0.0001);
    }

    /** 2 · El aparato vuelve de un corte: se recupera todo lo del corte. */
    #[Test]
    public function after_an_outage_the_energy_of_the_gap_is_recovered(): void
    {
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1000])->assertStatus(201);

        // Vuelve 17 h después sin avance (acaba de arrancar), pero su total de
        // vida ha seguido contando.
        $this->subir(['historical_energy_wh' => 1480], 17 * 60)->assertStatus(201);

        $this->assertEqualsWithDelta(480.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertFalse($this->ultimaLectura()->is_suspicious);
    }

    /**
     * 3 · Primera lectura con total de vida: se guarda como referencia y la
     * energía es el avance si llega, o 0; nunca potencia × tiempo.
     */
    #[Test]
    public function the_first_odometer_reading_uses_the_step_or_zero(): void
    {
        $this->subir(['historical_energy_wh' => 1000])->assertStatus(201);

        $this->assertEqualsWithDelta(0.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(1000.0, $this->referenciaWh(), 0.0001);
    }

    /** 4 · Total algo menor que el guardado: lectura corrupta, energía 0. */
    #[Test]
    public function a_slightly_lower_odometer_counts_zero_and_keeps_the_reference(): void
    {
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1000])->assertStatus(201);
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 990])->assertStatus(201);

        $this->assertEqualsWithDelta(0.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(1000.0, $this->referenciaWh(), 0.0001);

        // La siguiente buena se resta contra la referencia que no se movió.
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1010])->assertStatus(201);
        $this->assertEqualsWithDelta(10.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
    }

    /**
     * 5 · El total cae casi a cero (contador reseteado, aparato cambiado): se
     * abre otra sesión y la lectura va como la primera.
     */
    #[Test]
    public function a_real_odometer_reset_starts_over_like_a_first_reading(): void
    {
        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1000])->assertStatus(201);
        $this->subir(['energy_wh' => 4, 'historical_energy_wh' => 3])->assertStatus(201);

        $this->assertEqualsWithDelta(4.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertSame(2, (int) HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->consumo->id)
            ->max('session_index'));
    }

    /** 6 · Aparato sin total de vida: se suma su avance, como siempre. */
    #[Test]
    public function without_odometer_the_device_step_is_used(): void
    {
        $this->subir(['energy_wh' => 7])->assertStatus(201);

        $this->assertEqualsWithDelta(7.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
    }

    /** 7 · Ni total ni avance: potencia × tiempo, como siempre. */
    #[Test]
    public function without_odometer_or_step_power_times_time_is_used(): void
    {
        $this->subir([])->assertStatus(201);

        // 24 W × 300 s = 2 Wh.
        $this->assertEqualsWithDelta(2.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
        $this->assertSame('derived', $this->ultimaLectura()->energy_source);
    }

    /** 8 · Total de vida en Wh y no en Ah: cada magnitud por su lado. */
    #[Test]
    public function each_magnitude_goes_its_own_way(): void
    {
        $this->subir(['energy_wh' => 13, 'energy_ah' => 1, 'historical_energy_wh' => 1000])->assertStatus(201);
        $this->subir(['energy_wh' => 13, 'energy_ah' => 2, 'historical_energy_wh' => 1026])->assertStatus(201);

        $lectura = $this->ultimaLectura();
        $this->assertEqualsWithDelta(26.0, (float) $lectura->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(2.0, (float) $lectura->energy_ah, 0.0001);
    }

    /**
     * 9 · Salto imposible para lo que da el elemento: sospechosa, no suma y la
     * referencia no se mueve.
     */
    #[Test]
    public function an_impossible_jump_is_flagged_and_not_counted(): void
    {
        $this->consumo->update(['rated_power_w' => 240]);

        $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 1000])->assertStatus(201);

        // 5.000 Wh en 5 min con un elemento de 240 W.
        $respuesta = $this->subir(['energy_wh' => 13, 'historical_energy_wh' => 6000]);
        $respuesta->assertStatus(201);

        $this->assertTrue($this->ultimaLectura()->is_suspicious);
        $this->assertEqualsWithDelta(13.0, $this->whDelDia(), 0.0001);
        $this->assertEqualsWithDelta(1000.0, $this->referenciaWh(), 0.0001);

        // Dos escalones del contador en cinco minutos no son un salto imposible.
        $this->subir(['energy_wh' => 26, 'historical_energy_wh' => 1026])->assertStatus(201);
        $this->assertFalse($this->ultimaLectura()->is_suspicious);
        $this->assertEqualsWithDelta(26.0, (float) $this->ultimaLectura()->energy_wh, 0.0001);
    }
}
