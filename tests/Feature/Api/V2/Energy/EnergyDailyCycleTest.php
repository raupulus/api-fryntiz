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
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * El ciclo completo: se sube durante el día, pasa el cron, y los acumulados
 * del día y los totales tienen que seguir cuadrando.
 *
 * Es el recorrido que de verdad hace el dato:
 *
 * ```
 *  POST /energy/readings ──► hardware_energy_readings   (cada muestra)
 *            │                        │
 *            └── en vivo ─────────────┤
 *                                     ▼
 *                        hardware_energy_today          (una fila por día)
 *                                     │
 *              energy:aggregate-daily ┤  (de madrugada, cierra el día)
 *                                     ▼
 *                     hardware_energy_historical        (una fila por sesión)
 * ```
 *
 * Los dos caminos tienen que dar lo mismo: lo que se acumula en vivo lectura a
 * lectura y lo que el cron reconstruye sumando los resúmenes. Si divergen, el
 * panel enseña una cosa y las gráficas otra.
 *
 * **Todo en UTC.** Las lecturas se guardan con `created_at` en UTC, el `date`
 * del resumen se calcula en UTC y el comando corta el día en UTC. A
 * `Europe/Madrid` se traduce sólo al pintar.
 */
class EnergyDailyCycleTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    /** Monitor sin odómetro: sus totales los calculamos nosotros. */
    private HardwareDevice $monitor;

    private HardwareEnergy $routerChannel;

    private HardwareEnergy $serverChannel;

    /** Controlador solar: lleva sus propias cuentas. */
    private HardwareDevice $solarController;

    private HardwareEnergy $solarPanel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->monitor = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Monitor INA3221',
        ]);

        $router = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Router']);
        $server = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Servidor']);

        $this->routerChannel = HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $router->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'auto_calculate_history' => true,
            'is_active' => true,
        ]);

        $this->serverChannel = HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $server->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 1,
            'nominal_voltage' => 19.0,
            'auto_calculate_history' => true,
            'is_active' => true,
        ]);

        $this->solarController = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20LI',
        ]);

        $this->solarPanel = HardwareEnergy::create([
            'hardware_device_id' => $this->solarController->id,
            'hardware_device_monitorized_id' => $this->solarController->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'auto_calculate_history' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Una hora de consumo del monitor: 2 A a 12 V y 3 A a 19 V.
     *
     * Con `duration = 3600` cada subida vale exactamente 24 Wh en el canal 0 y
     * 57 Wh en el canal 1, que son números redondos y fáciles de seguir.
     */
    private function subirHoraDeConsumo(): void
    {
        $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->monitor->id,
                'duration' => 3600,
                'energy' => [
                    'loads' => [
                        ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0],
                        ['channel' => 1, 'voltage' => 19.0, 'amperage' => 3.0],
                    ],
                ],
            ],
            $this->deviceHeaders($this->monitor, [TokenAbilities::ENERGY_WRITE])
        )->assertStatus(201);
    }

    #[Test]
    public function the_cron_closes_the_day_with_what_the_live_ingestion_already_added(): void
    {
        $ayer = Carbon::now('UTC')->subDay();

        // Tres subidas repartidas por el día de ayer.
        foreach ([8, 12, 18] as $hora) {
            $this->travelTo($ayer->copy()->setTime($hora, 0));
            $this->subirHoraDeConsumo();
        }

        $this->travelBack();

        $fecha = $ayer->toDateString();

        // Lo que dejó la ingesta en vivo, antes de que pase el cron.
        $enVivo = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->where('date', $fecha)
            ->firstOrFail();

        $this->assertSame(3, $enVivo->readings_count);
        $this->assertEqualsWithDelta(72.0, (float) $enVivo->energy_wh, 0.0001);

        // Y ahora el cierre de madrugada.
        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $tras = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->where('date', $fecha)
            ->firstOrFail();

        $this->assertSame(3, $tras->readings_count, 'El cron recuenta, no duplica.');
        $this->assertEqualsWithDelta(72.0, (float) $tras->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $tras->energy_ah, 0.0001);

        // Extremos del día, que en vivo se van ajustando muestra a muestra.
        $this->assertEqualsWithDelta(12.0, (float) $tras->voltage_min, 0.0001);
        $this->assertEqualsWithDelta(12.0, (float) $tras->voltage_max, 0.0001);
        $this->assertEqualsWithDelta(24.0, (float) $tras->power_max, 0.0001);

        // El otro canal, con sus propios números.
        $server = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->serverChannel->id)
            ->where('date', $fecha)
            ->firstOrFail();

        $this->assertEqualsWithDelta(171.0, (float) $server->energy_wh, 0.0001);
    }

    #[Test]
    public function the_historical_total_is_the_sum_of_the_daily_summaries(): void
    {
        $hoy = Carbon::now('UTC');

        // Tres días seguidos con una subida cada uno.
        foreach ([3, 2, 1] as $diasAtras) {
            $this->travelTo($hoy->copy()->subDays($diasAtras)->setTime(10, 0));
            $this->subirHoraDeConsumo();
        }

        $this->travelBack();

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $historico = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail();

        $this->assertSame(3, $historico->days_operating, 'Días distintos con lecturas, no número de filas.');
        $this->assertSame(3, $historico->readings_count);
        $this->assertEqualsWithDelta(72.0, (float) $historico->energy_wh, 0.0001);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $historico->energy_wh_source);

        // Y cuadra con la suma de los resúmenes diarios, que es de donde sale.
        $sumaDiaria = (float) HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->sum('energy_wh');

        $this->assertEqualsWithDelta($sumaDiaria, (float) $historico->energy_wh, 0.0001);
    }

    #[Test]
    public function running_the_cron_twice_does_not_change_the_result(): void
    {
        $ayer = Carbon::now('UTC')->subDay();

        foreach ([8, 12] as $hora) {
            $this->travelTo($ayer->copy()->setTime($hora, 0));
            $this->subirHoraDeConsumo();
        }

        $this->travelBack();

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $primera = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail()
            ->only(['days_operating', 'readings_count', 'energy_wh', 'energy_ah']);

        $this->artisan('energy:aggregate-daily')->assertSuccessful();
        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $tercera = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail()
            ->only(['days_operating', 'readings_count', 'energy_wh', 'energy_ah']);

        $this->assertEquals($primera, $tercera, 'El cierre diario tiene que ser idempotente.');

        // Y sigue habiendo una sola fila de cada cosa.
        $this->assertSame(1, HardwareEnergyHistorical::query()->where('hardware_energy_id', $this->routerChannel->id)->count());
        $this->assertSame(1, HardwareEnergyToday::query()->where('hardware_energy_id', $this->routerChannel->id)->count());
    }

    #[Test]
    public function the_current_day_can_be_consolidated_without_waiting_for_midnight(): void
    {
        $this->subirHoraDeConsumo();
        $this->subirHoraDeConsumo();

        $this->artisan('energy:aggregate-daily --today')->assertSuccessful();

        $hoy = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->where('date', Carbon::now('UTC')->toDateString())
            ->firstOrFail();

        $this->assertSame(2, $hoy->readings_count);
        $this->assertEqualsWithDelta(48.0, (float) $hoy->energy_wh, 0.0001);
    }

    #[Test]
    public function suspicious_readings_stay_out_of_both_the_day_and_the_total(): void
    {
        $ayer = Carbon::now('UTC')->subDay();

        $this->travelTo($ayer->copy()->setTime(10, 0));
        $this->subirHoraDeConsumo();

        // Una lectura con corriente negativa: se guarda, pero marcada.
        $this->travelTo($ayer->copy()->setTime(11, 0));
        $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->monitor->id,
                'duration' => 3600,
                'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => -50.0]]],
            ],
            $this->deviceHeaders($this->monitor, [TokenAbilities::ENERGY_WRITE])
        )->assertStatus(201);

        $this->travelBack();

        $this->assertSame(1, HardwareEnergyReading::query()->where('is_suspicious', true)->count());

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $resumen = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail();

        $this->assertSame(1, $resumen->readings_count, 'La sospechosa no se cuenta.');
        $this->assertEqualsWithDelta(24.0, (float) $resumen->energy_wh, 0.0001);
    }

    #[Test]
    public function the_cron_does_not_touch_a_controller_that_keeps_its_own_counters(): void
    {
        $ayer = Carbon::now('UTC')->subDay();

        $this->travelTo($ayer->copy()->setTime(10, 0));

        $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->solarController->id,
                'duration' => 3600,
                'energy' => [
                    'generator' => [
                        'voltage' => 34.5,
                        'amperage' => 4.2,
                        'today_energy_wh' => 1250.0,
                        'historical_energy_wh' => 45000.0,
                        'total_operating_days' => 1745,
                    ],
                ],
            ],
            $this->deviceHeaders($this->solarController, [TokenAbilities::ENERGY_WRITE])
        )->assertStatus(201);

        $this->travelBack();

        // Ni con --all, que es lo que fuerza a procesar los elementos con
        // contadores nativos: sus totales son suyos y cubren años que nosotros
        // no hemos medido.
        $this->artisan('energy:aggregate-daily --all')->assertSuccessful();

        $historico = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->solarPanel->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(45000.0, (float) $historico->energy_wh, 0.0001);
        $this->assertSame(1745, $historico->days_operating);

        // El resumen del día sí se consolida: ahí manda el total que da el
        // aparato para ese día, no la suma de nuestros intervalos.
        $resumen = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->solarPanel->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(1250.0, (float) $resumen->energy_wh, 0.0001);
    }

    #[Test]
    public function a_day_without_readings_leaves_everything_untouched(): void
    {
        $ayer = Carbon::now('UTC')->subDay();

        $this->travelTo($ayer->copy()->setTime(10, 0));
        $this->subirHoraDeConsumo();
        $this->travelBack();

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $antes = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail()
            ->only(['days_operating', 'energy_wh']);

        // Un día más tarde, sin que haya entrado nada.
        $this->travelTo(Carbon::now('UTC')->addDay());
        $this->artisan('energy:aggregate-daily')->assertSuccessful();
        $this->travelBack();

        $despues = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail()
            ->only(['days_operating', 'energy_wh']);

        $this->assertEquals($antes, $despues);
    }

    #[Test]
    public function readings_near_midnight_land_on_the_utc_day_they_belong_to(): void
    {
        // Las dos fechas se fijan antes de viajar: dentro de `travelTo` el
        // reloj ya está congelado y `now()` deja de ser el de verdad.
        $hoy = Carbon::now('UTC')->startOfDay();
        $ayer = $hoy->copy()->subDay();

        // 23:50 UTC de ayer: en Madrid ya es el día siguiente, pero el dato se
        // guarda en UTC y es el día de ayer quien tiene que contarlo.
        $this->travelTo($ayer->copy()->setTime(23, 50));
        $this->subirHoraDeConsumo();

        // 00:10 UTC de hoy: el día siguiente, aunque sean veinte minutos después.
        $this->travelTo($hoy->copy()->setTime(0, 10));
        $this->subirHoraDeConsumo();

        $this->travelBack();

        $this->artisan('energy:aggregate-daily')->assertSuccessful();

        $filas = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(2, $filas, 'Cada lectura en el día UTC que le toca.');
        $this->assertSame($ayer->toDateString(), $filas[0]->date->toDateString());
        $this->assertSame(1, $filas[0]->readings_count);
        $this->assertSame($hoy->toDateString(), $filas[1]->date->toDateString());
        $this->assertSame(1, $filas[1]->readings_count);
    }
}
