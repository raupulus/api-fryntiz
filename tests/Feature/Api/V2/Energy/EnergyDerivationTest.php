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
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Qué se calcula y qué no cuando la subida viene con huecos.
 *
 * El contrato decía «lo que no mandes se calcula», y eso leído a secas invita a
 * mandar nulos esperando que el servidor los rellene. **No los rellena.** Cada
 * magnitud sale de otras concretas, y si esas no están, la magnitud se queda a
 * `null`: inventar un 0 convertiría «no tengo dato» en «medí cero» y bajaría
 * todas las medias.
 *
 * El orden de preferencia, que es lo que fijan estas pruebas:
 *
 * | | Lo que manda el aparato | Si no | Y si no |
 * |---|---|---|---|
 * | Potencia | `power` | `V · A` | `null` |
 * | Vatios-hora | `energy_wh` | `A · V · s / 3600` | `P · s / 3600` |
 * | Amperios-hora | `energy_ah` | `A · s / 3600` | `Wh / V` |
 *
 * El caso que más duele y el que motivó juntarlo todo: un aparato que mide
 * **sólo potencia** —los hay— registraba `energy_wh` a nulo y su resumen del día
 * sumaba cero, con un 201 y sin un aviso.
 */
class EnergyDerivationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $elemento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Nodo']);
    }

    private function darDeAlta(?float $nominal = 12.0): void
    {
        $this->elemento = HardwareEnergy::create(array_filter([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => $nominal,
            'is_active' => true,
        ], static fn ($v) => $v !== null));
    }

    /**
     * @param  array<string, mixed>  $consumo
     */
    private function subir(array $consumo, int $duracion = 600): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->device->id,
                'duration' => $duracion,
                'energy' => ['loads' => [$consumo + ['channel' => 0]]],
            ],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    private function lectura(): HardwareEnergyReading
    {
        return HardwareEnergyReading::query()->firstOrFail();
    }

    // ── Lo que el aparato manda, se respeta ────────────────────────────────

    #[Test]
    public function what_the_device_sends_is_never_recalculated(): void
    {
        // Aunque no cuadre con V · A: es su medida y su instrumento.
        $this->darDeAlta();

        $this->subir([
            'voltage' => 12.0, 'amperage' => 2.0,
            'power' => 99.0, 'energy_wh' => 88.0, 'energy_ah' => 77.0,
        ])->assertStatus(201);

        $lectura = $this->lectura();

        $this->assertEqualsWithDelta(99.0, (float) $lectura->power, 0.0001);
        $this->assertEqualsWithDelta(88.0, (float) $lectura->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(77.0, (float) $lectura->energy_ah, 0.0001);
    }

    // ── Lo que falta, se calcula de lo que hay ────────────────────────────

    #[Test]
    public function with_voltage_and_amperage_everything_else_comes_out(): void
    {
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0, 'amperage' => 2.0])->assertStatus(201);

        $lectura = $this->lectura();

        $this->assertEqualsWithDelta(24.0, (float) $lectura->power, 0.0001, 'P = V · A');
        $this->assertEqualsWithDelta(4.0, (float) $lectura->energy_wh, 0.0001, 'Wh = 24 W · 600 s / 3600');
        $this->assertEqualsWithDelta(1 / 3, (float) $lectura->energy_ah, 0.0001, 'Ah = 2 A · 600 s / 3600');
    }

    #[Test]
    public function with_only_power_the_energy_still_comes_out(): void
    {
        // Hay sensores que dan vatios y no amperios. Antes esto registraba
        // `energy_wh` a nulo y el resumen del día sumaba cero.
        $this->darDeAlta();

        $this->subir(['power' => 24.0])->assertStatus(201);

        $lectura = $this->lectura();

        $this->assertEqualsWithDelta(24.0, (float) $lectura->power, 0.0001);
        $this->assertEqualsWithDelta(4.0, (float) $lectura->energy_wh, 0.0001, 'Wh = P · s / 3600');
        $this->assertEqualsWithDelta(1 / 3, (float) $lectura->energy_ah, 0.0001, 'Ah = Wh / V nominal');
    }

    #[Test]
    public function with_only_power_the_day_summary_is_not_zero(): void
    {
        $this->darDeAlta();

        $this->subir(['power' => 24.0])->assertStatus(201);

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertEqualsWithDelta(4.0, (float) $resumen->energy_wh, 0.0001);
        $this->assertGreaterThan(0.0, (float) $resumen->energy_ah);
    }

    #[Test]
    public function without_voltage_the_nominal_of_the_element_is_used(): void
    {
        $this->darDeAlta(nominal: 12.0);

        $respuesta = $this->subir(['amperage' => 2.0]);

        $respuesta->assertStatus(201);
        $this->assertSame('nominal', $respuesta->json('data.0.sources.voltage'));
        $this->assertEqualsWithDelta(24.0, (float) $this->lectura()->power, 0.0001);
        $this->assertEqualsWithDelta(4.0, (float) $this->lectura()->energy_wh, 0.0001);
    }

    // ── Lo que no se puede calcular, se queda a null ──────────────────────

    #[Test]
    public function without_amperage_and_without_power_there_is_no_energy(): void
    {
        // Y **no se guarda un 0**: un 0 diría que se midió y dio cero.
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0])->assertStatus(201);

        $lectura = $this->lectura();

        $this->assertNull($lectura->power);
        $this->assertNull($lectura->energy_wh);
        $this->assertNull($lectura->energy_ah);
    }

    #[Test]
    public function a_reading_with_no_energy_still_counts_as_a_reading(): void
    {
        // Ojo con esto: la fila del día se crea igual y `readings_count` sube.
        // Subir muestras vacías no rompe nada pero ensucia el recuento.
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0])->assertStatus(201);

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertSame(1, $resumen->readings_count);
        $this->assertEqualsWithDelta(0.0, (float) $resumen->energy_wh, 0.0001);
    }

    #[Test]
    public function an_explicit_null_is_the_same_as_not_sending_it(): void
    {
        $this->darDeAlta();

        $this->subir(['voltage' => null, 'amperage' => 2.0])->assertStatus(201);

        // La tensión sale de la nominal, igual que si se hubiera omitido.
        $this->assertEqualsWithDelta(12.0, (float) $this->lectura()->voltage, 0.0001);
        $this->assertEqualsWithDelta(4.0, (float) $this->lectura()->energy_wh, 0.0001);
    }

    #[Test]
    public function without_voltage_and_without_nominal_the_reading_is_suspicious(): void
    {
        // Sin tensión no hay vatios. La lectura se guarda —la corriente es
        // buena— pero marcada y fuera de todos los agregados.
        $this->darDeAlta(nominal: null);

        $respuesta = $this->subir(['amperage' => 2.0]);

        $respuesta->assertStatus(201);
        $this->assertTrue($respuesta->json('data.0.is_suspicious'));
        $this->assertNotEmpty($respuesta->json('warnings'));

        $this->assertNull($this->lectura()->energy_wh);
        $this->assertEqualsWithDelta(1 / 3, (float) $this->lectura()->energy_ah, 0.0001, 'Los Ah no necesitan tensión.');

        $this->assertSame(0, HardwareEnergyToday::query()->count(), 'Una sospechosa no crea resumen.');
        $this->assertSame(0, HardwareEnergyHistorical::query()->count());
    }

    // ── La duración multiplica ────────────────────────────────────────────

    #[Test]
    public function the_duration_scales_the_energy_and_not_the_power(): void
    {
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0, 'amperage' => 2.0], duracion: 3600)->assertStatus(201);

        $lectura = $this->lectura();

        $this->assertEqualsWithDelta(24.0, (float) $lectura->power, 0.0001, 'La potencia no depende del intervalo.');
        $this->assertEqualsWithDelta(24.0, (float) $lectura->energy_wh, 0.0001, 'Una hora a 24 W son 24 Wh.');
        $this->assertEqualsWithDelta(2.0, (float) $lectura->energy_ah, 0.0001);
    }

    // ── Cómo se construyen los acumulados ─────────────────────────────────

    #[Test]
    public function the_day_summary_adds_up_the_intervals(): void
    {
        $this->darDeAlta();

        // Tres muestras de 10 minutos a 24 W: 4 Wh cada una.
        foreach ([1, 2, 3] as $ignorado) {
            $this->subir(['voltage' => 12.0, 'amperage' => 2.0])->assertStatus(201);
        }

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertSame(3, $resumen->readings_count);
        $this->assertEqualsWithDelta(12.0, (float) $resumen->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(1.0, (float) $resumen->energy_ah, 0.0001);
    }

    #[Test]
    public function the_lifetime_total_adds_up_the_same_intervals(): void
    {
        $this->darDeAlta();

        foreach ([1, 2, 3] as $ignorado) {
            $this->subir(['voltage' => 12.0, 'amperage' => 2.0])->assertStatus(201);
        }

        $acumulado = HardwareEnergyHistorical::query()->firstOrFail();

        $this->assertEqualsWithDelta(12.0, (float) $acumulado->energy_wh, 0.0001);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $acumulado->energy_wh_source);
    }

    #[Test]
    public function a_declared_day_total_replaces_instead_of_adding(): void
    {
        // Es el contador del aparato, no un incremento: sumarlo lo contaría dos
        // veces. Tres subidas declarando 100 Wh dejan 100, no 300.
        $this->darDeAlta();

        foreach ([100.0, 150.0, 180.0] as $total) {
            $this->subir([
                'voltage' => 12.0, 'amperage' => 2.0, 'today_energy_wh' => $total,
            ])->assertStatus(201);
        }

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertEqualsWithDelta(180.0, (float) $resumen->energy_wh, 0.0001);
        $this->assertSame(3, $resumen->readings_count, 'Las lecturas sí se cuentan.');
    }

    #[Test]
    public function a_declared_day_total_and_a_calculated_one_do_not_mix(): void
    {
        // Si el aparato declara los Wh pero no los Ah, los Wh son suyos y los
        // Ah se siguen sumando de las lecturas.
        $this->darDeAlta();

        foreach ([1, 2] as $ignorado) {
            $this->subir([
                'voltage' => 12.0, 'amperage' => 2.0, 'today_energy_wh' => 100.0,
            ])->assertStatus(201);
        }

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertEqualsWithDelta(100.0, (float) $resumen->energy_wh, 0.0001, 'Suyos.');
        $this->assertEqualsWithDelta(2 / 3, (float) $resumen->energy_ah, 0.0001, 'Nuestros: 2 × 0,333 Ah.');
    }

    #[Test]
    public function a_declared_lifetime_total_never_goes_down(): void
    {
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0, 'amperage' => 2.0, 'historical_energy_wh' => 5000.0])
            ->assertStatus(201);

        // Una lectura corrupta que retrocede un poco no borra lo que había.
        $this->subir(['voltage' => 12.0, 'amperage' => 2.0, 'historical_energy_wh' => 4900.0])
            ->assertStatus(201);

        $acumulado = HardwareEnergyHistorical::query()->firstOrFail();

        $this->assertEqualsWithDelta(5000.0, (float) $acumulado->energy_wh, 0.0001);
        $this->assertSame(1, $acumulado->session_index);
    }

    #[Test]
    public function a_lifetime_total_that_collapses_opens_a_new_session(): void
    {
        // Un reinicio de verdad: el contador cae a menos de la mitad.
        $this->darDeAlta();

        $this->subir(['voltage' => 12.0, 'amperage' => 2.0, 'historical_energy_wh' => 5000.0])
            ->assertStatus(201);
        $this->subir(['voltage' => 12.0, 'amperage' => 2.0, 'historical_energy_wh' => 30.0])
            ->assertStatus(201);

        $sesiones = HardwareEnergyHistorical::query()->orderBy('session_index')->get();

        $this->assertCount(2, $sesiones);
        $this->assertEqualsWithDelta(5000.0, (float) $sesiones[0]->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(30.0, (float) $sesiones[1]->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(5030.0, (float) $sesiones->sum('energy_wh'), 0.0001, 'El total es la suma.');
    }
}
