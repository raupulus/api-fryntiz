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
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * La instalación entera subiendo por el contrato universal `energy`.
 *
 * Monta los dos montajes que existen de verdad y comprueba que una subida de
 * cada uno reparte bien en las tres tablas:
 *
 * **1. Controlador solar (un aparato, tres papeles).** Un Renogy Rover mide su
 * panel, su banco de baterías y su salida de carga. Son tres filas de
 * `hardware_energy` del mismo dispositivo, una por `role`, todas en el canal 0:
 * un controlador no es un monitor de varios canales, es un aparato entero.
 *
 * **2. Monitor multicanal (un aparato, tres consumos ajenos).** Un monitor con
 * tres pinzas mide tres aparatos distintos. Son tres filas de `hardware_energy`
 * del mismo dispositivo, todas con `role = load`, cada una en su
 * `sensor_position` y apuntando con `hardware_device_monitorized_id` al aparato
 * que mide. Es el caso que obliga a tener tensión por elemento: un servidor a
 * 19 V y una Raspberry a 5 V en la misma petición.
 *
 * Lo que se verifica en los dos: que cada lectura va al elemento que le toca,
 * que la energía del intervalo se deriva con la tensión de *su* elemento, y que
 * los acumulados del aparato mandan sobre lo calculado cuando vienen.
 */
class EnergyInstallationScenarioTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    /** Controlador solar: un aparato con los tres papeles. */
    private HardwareDevice $solarController;

    private HardwareEnergy $solarPanel;

    private HardwareEnergy $solarBattery;

    private HardwareEnergy $solarLoad;

    /** Monitor multicanal: un aparato que mide otros tres. */
    private HardwareDevice $multiMonitor;

    private HardwareEnergy $routerChannel;

    private HardwareEnergy $serverChannel;

    private HardwareEnergy $piChannel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        // ── Montaje 1: el controlador solar ──────────────────────────────
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
            // El controlador lleva su propio odómetro: el cron no le recalcula
            // los totales desde nuestras lecturas.
            'auto_calculate_history' => false,
            'is_active' => true,
        ]);

        $this->solarBattery = HardwareEnergy::create([
            'hardware_device_id' => $this->solarController->id,
            'hardware_device_monitorized_id' => $this->solarController->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.8,
            'voltage_min' => 11.0,
            'voltage_max' => 14.4,
            'capacity_ah' => 100.0,
            'auto_calculate_history' => false,
            'is_active' => true,
        ]);

        $this->solarLoad = HardwareEnergy::create([
            'hardware_device_id' => $this->solarController->id,
            'hardware_device_monitorized_id' => $this->solarController->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'auto_calculate_history' => false,
            'is_active' => true,
        ]);

        // ── Montaje 2: el monitor de tres canales ────────────────────────
        $this->multiMonitor = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Monitor INA3221',
        ]);

        $router = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Router']);
        $server = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Servidor']);
        $pi = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Raspberry Pi']);

        $this->routerChannel = HardwareEnergy::create([
            'hardware_device_id' => $this->multiMonitor->id,
            'hardware_device_monitorized_id' => $router->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);

        $this->serverChannel = HardwareEnergy::create([
            'hardware_device_id' => $this->multiMonitor->id,
            'hardware_device_monitorized_id' => $server->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 1,
            'nominal_voltage' => 19.0,
            'is_active' => true,
        ]);

        $this->piChannel = HardwareEnergy::create([
            'hardware_device_id' => $this->multiMonitor->id,
            'hardware_device_monitorized_id' => $pi->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 2,
            'nominal_voltage' => 5.0,
            'is_active' => true,
        ]);
    }

    private function subir(HardwareDevice $device, array $payload)
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->deviceHeaders($device, [TokenAbilities::ENERGY_WRITE])
        );
    }

    // ─────────────────────── Controlador solar ──────────────────────────

    #[Test]
    public function a_solar_controller_upload_fills_the_three_roles_at_once(): void
    {
        $response = $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => [
                    'voltage' => 34.5,
                    'amperage' => 4.2,
                    'power' => 144.9,
                    'temperature' => 31.5,
                    'charging_status' => 3,
                    'charging_status_label' => 'mppt',
                    'light_status' => false,
                    'today_energy_wh' => 1250.0,
                    'historical_energy_wh' => 45000.0,
                ],
                'battery' => [
                    'voltage' => 13.4,
                    'amperage' => 5.0,
                    'soc' => 92,
                    'temperature' => 24.5,
                    'today_energy_ah' => 40.0,
                    'historical_energy_ah' => 1500.0,
                    'battery_full_charges' => 25,
                    'battery_over_discharges' => 1,
                ],
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => 12.1,
                        'amperage' => 2.5,
                        'today_energy_wh' => 310.0,
                        'historical_energy_wh' => 12500.0,
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        // Una subida, tres lecturas: una por papel.
        $this->assertSame(3, HardwareEnergyReading::query()->count());

        // ── Generación ───────────────────────────────────────────────────
        $panel = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->solarPanel->id)
            ->firstOrFail();

        $this->assertSame(34.5, (float) $panel->voltage);
        $this->assertSame(4.2, (float) $panel->amperage);
        $this->assertSame(144.9, (float) $panel->power, 'La potencia que manda el aparato manda sobre V×A.');
        $this->assertSame(60, $panel->delta_seconds);
        // Manda total de vida: es la primera lectura, así que sólo fija la
        // referencia y su energía es 0. No se calcula potencia × tiempo para un
        // aparato que lleva contadores.
        $this->assertEqualsWithDelta(0.0, (float) $panel->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $panel->energy_ah, 0.0001);
        $this->assertSame('measured', $panel->voltage_source);
        $this->assertSame(3, $panel->charging_status);
        $this->assertSame('mppt', $panel->charging_status_label);
        $this->assertFalse((bool) $panel->is_suspicious);

        // ── Batería ──────────────────────────────────────────────────────
        $battery = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->solarBattery->id)
            ->firstOrFail();

        $this->assertSame(13.4, (float) $battery->voltage);
        $this->assertSame(13.4, (float) $battery->battery_voltage);
        $this->assertSame(92, $battery->battery_percentage);
        // Primera lectura con total de vida: fija la referencia, energía 0.
        $this->assertEqualsWithDelta(0.0, (float) $battery->energy_ah, 0.0001);

        // ── Consumo de la salida de carga ────────────────────────────────
        $load = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->solarLoad->id)
            ->firstOrFail();

        $this->assertSame(12.1, (float) $load->voltage);
        $this->assertEqualsWithDelta(30.25, (float) $load->power, 0.0001, 'Sin potencia del aparato se deriva V×A.');
        // Primera lectura con total de vida: fija la referencia, energía 0.
        $this->assertEqualsWithDelta(0.0, (float) $load->energy_wh, 0.0001);
    }

    #[Test]
    public function the_device_own_daily_totals_win_over_what_we_calculate(): void
    {
        $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => ['voltage' => 34.5, 'amperage' => 4.2, 'today_energy_wh' => 1250.0],
                'battery' => ['voltage' => 13.4, 'amperage' => 5.0, 'soc' => 92, 'today_energy_ah' => 40.0],
                'loads' => [['channel' => 0, 'voltage' => 12.1, 'amperage' => 2.5, 'today_energy_wh' => 310.0]],
            ],
        ])->assertStatus(201);

        // Un controlador lleva su propia cuenta del día. Sumarle encima lo que
        // calculamos nosotros daría el doble: su total sustituye, no se suma.
        $this->assertSame(
            1250.0,
            (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->solarPanel->id)->firstOrFail()->energy_wh
        );
        $this->assertSame(
            40.0,
            (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->solarBattery->id)->firstOrFail()->energy_ah
        );
        $this->assertSame(
            310.0,
            (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->solarLoad->id)->firstOrFail()->energy_wh
        );
    }

    #[Test]
    public function the_controller_odometer_feeds_the_historical_totals(): void
    {
        $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => [
                    'voltage' => 34.5,
                    'amperage' => 4.2,
                    'historical_energy_wh' => 45000.0,
                    'total_operating_days' => 1745,
                ],
                'battery' => [
                    'voltage' => 13.4,
                    'amperage' => 5.0,
                    'historical_energy_ah' => 1500.0,
                    'battery_full_charges' => 25,
                    'battery_over_discharges' => 1,
                ],
                'loads' => [
                    ['channel' => 0, 'voltage' => 12.1, 'amperage' => 2.5, 'historical_energy_wh' => 12500.0],
                ],
            ],
        ])->assertStatus(201);

        $panelHist = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->solarPanel->id)
            ->firstOrFail();

        $this->assertSame(45000.0, (float) $panelHist->energy_wh);
        $this->assertSame(1745, $panelHist->days_operating);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $panelHist->energy_wh_source);
        $this->assertSame(1, $panelHist->session_index);

        $batteryHist = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->solarBattery->id)
            ->firstOrFail();

        $this->assertSame(1500.0, (float) $batteryHist->energy_ah);
        $this->assertSame(25, $batteryHist->number_battery_full_charges);
        $this->assertSame(1, $batteryHist->number_battery_over_discharges);

        $loadHist = HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $this->solarLoad->id)
            ->firstOrFail();

        $this->assertSame(12500.0, (float) $loadHist->energy_wh);
    }

    #[Test]
    public function a_battery_soc_is_inferred_from_its_voltage_calibration(): void
    {
        // 12,7 V entre 11,0 y 14,4 → (12,7-11,0)/(14,4-11,0) = 50 %
        $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => ['battery' => ['voltage' => 12.7, 'amperage' => 1.0]],
        ])->assertStatus(201);

        $battery = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->solarBattery->id)
            ->firstOrFail();

        $this->assertSame(50, $battery->battery_percentage);
    }

    // ──────────────────────── Monitor multicanal ────────────────────────

    #[Test]
    public function a_three_channel_monitor_splits_each_load_into_its_own_element(): void
    {
        $response = $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 300,
            'energy' => [
                'loads' => [
                    ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0],
                    ['channel' => 1, 'voltage' => 19.0, 'amperage' => 3.0],
                    ['channel' => 2, 'voltage' => 5.0, 'amperage' => 1.2],
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertSame(3, HardwareEnergyReading::query()->count());

        // Cada canal a su elemento, y ningún canal se cuela en otro.
        $router = HardwareEnergyReading::query()->where('hardware_energy_id', $this->routerChannel->id)->firstOrFail();
        $server = HardwareEnergyReading::query()->where('hardware_energy_id', $this->serverChannel->id)->firstOrFail();
        $pi = HardwareEnergyReading::query()->where('hardware_energy_id', $this->piChannel->id)->firstOrFail();

        // **Cada uno con su tensión.** Es la razón de ser de `hardware_energy`:
        // con una sola tensión por petición, 19 V y 5 V darían vatios absurdos.
        $this->assertEqualsWithDelta(24.0, (float) $router->power, 0.0001);
        $this->assertEqualsWithDelta(57.0, (float) $server->power, 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $pi->power, 0.0001);

        // Wh del intervalo = A · 300/3600 · V
        $this->assertEqualsWithDelta(2.0, (float) $router->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(4.75, (float) $server->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(0.5, (float) $pi->energy_wh, 0.0001);

        // Todas las lecturas cuelgan del monitor que mide, no del aparato medido.
        $this->assertSame(
            3,
            HardwareEnergyReading::query()->where('hardware_device_id', $this->multiMonitor->id)->count()
        );
    }

    #[Test]
    public function a_channel_without_measured_voltage_falls_back_to_its_nominal(): void
    {
        // Una pinza de corriente sola no mide tensión: se usa la nominal del
        // elemento y se deja dicho en `voltage_source`, porque un vatio con
        // tensión supuesta no vale lo mismo que uno medido.
        $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 300,
            'energy' => ['loads' => [['channel' => 1, 'amperage' => 3.0]]],
        ])->assertStatus(201);

        $server = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->serverChannel->id)
            ->firstOrFail();

        $this->assertSame(19.0, (float) $server->voltage);
        $this->assertSame('nominal', $server->voltage_source);
        $this->assertEqualsWithDelta(57.0, (float) $server->power, 0.0001);
        $this->assertFalse((bool) $server->is_suspicious, 'Usar la nominal no convierte la lectura en sospechosa.');
    }

    #[Test]
    public function each_channel_keeps_its_own_daily_summary(): void
    {
        // Dos subidas seguidas: los consumos derivados se acumulan por canal.
        foreach (range(1, 2) as $ignored) {
            $this->subir($this->multiMonitor, [
                'hardware_device_id' => $this->multiMonitor->id,
                'duration' => 300,
                'energy' => [
                    'loads' => [
                        ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0],
                        ['channel' => 1, 'voltage' => 19.0, 'amperage' => 3.0],
                        ['channel' => 2, 'voltage' => 5.0, 'amperage' => 1.2],
                    ],
                ],
            ])->assertStatus(201);
        }

        $this->assertSame(3, HardwareEnergyToday::query()->count(), 'Una fila por elemento y día, no una por lectura.');

        $router = HardwareEnergyToday::query()->where('hardware_energy_id', $this->routerChannel->id)->firstOrFail();
        $server = HardwareEnergyToday::query()->where('hardware_energy_id', $this->serverChannel->id)->firstOrFail();

        $this->assertSame(2, $router->readings_count);
        $this->assertEqualsWithDelta(4.0, (float) $router->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(9.5, (float) $server->energy_wh, 0.0001);
    }

    #[Test]
    public function an_unknown_channel_is_registered_and_reported_back(): void
    {
        // El canal 7 no está dado de alta. Se crea para no perder el dato, pero
        // sin tensión nominal inventada, y la respuesta lo dice en `warnings`
        // para que no pase desapercibido durante meses.
        $response = $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 300,
            'energy' => ['loads' => [['channel' => 7, 'voltage' => 12.0, 'amperage' => 1.0]]],
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString('dado de alta', implode(' ', $response->json('warnings')));

        $nuevo = HardwareEnergy::query()
            ->where('hardware_device_id', $this->multiMonitor->id)
            ->where('sensor_position', 7)
            ->firstOrFail();

        $this->assertNull($nuevo->nominal_voltage);
        $this->assertSame(1, HardwareEnergyReading::query()->where('hardware_energy_id', $nuevo->id)->count());
    }

    #[Test]
    public function a_negative_current_is_stored_but_flagged_out_of_the_summaries(): void
    {
        $response = $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 300,
            'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => -2.0]]],
        ]);

        $response->assertStatus(201);
        $this->assertStringContainsString('negativa', implode(' ', $response->json('warnings')));

        $lectura = HardwareEnergyReading::query()->where('hardware_energy_id', $this->routerChannel->id)->firstOrFail();

        $this->assertTrue((bool) $lectura->is_suspicious);
        $this->assertSame(
            0,
            HardwareEnergyToday::query()->where('hardware_energy_id', $this->routerChannel->id)->count(),
            'Una lectura marcada no entra en los agregados.'
        );
    }

    // ───────────────── Los dos montajes a la vez ────────────────────────

    #[Test]
    public function both_installations_coexist_without_mixing_their_readings(): void
    {
        $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => ['voltage' => 34.5, 'amperage' => 4.2],
                'battery' => ['voltage' => 13.4, 'amperage' => 5.0],
                'loads' => [['channel' => 0, 'voltage' => 12.1, 'amperage' => 2.5]],
            ],
        ])->assertStatus(201);

        $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 300,
            'energy' => [
                'loads' => [
                    ['channel' => 0, 'voltage' => 12.0, 'amperage' => 2.0],
                    ['channel' => 1, 'voltage' => 19.0, 'amperage' => 3.0],
                    ['channel' => 2, 'voltage' => 5.0, 'amperage' => 1.2],
                ],
            ],
        ])->assertStatus(201);

        // Seis lecturas, seis elementos, seis resúmenes del día. Ninguna se ha
        // ido al dispositivo del otro montaje.
        $this->assertSame(6, HardwareEnergyReading::query()->count());
        $this->assertSame(3, HardwareEnergyReading::query()->where('hardware_device_id', $this->solarController->id)->count());
        $this->assertSame(3, HardwareEnergyReading::query()->where('hardware_device_id', $this->multiMonitor->id)->count());
        $this->assertSame(6, HardwareEnergyToday::query()->count());

        // El generador sólo existe en el montaje solar.
        $this->assertSame(
            1,
            HardwareEnergyReading::query()
                ->whereHas('hardwareEnergy', fn ($q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR))
                ->count()
        );

        // Y hay cuatro consumos: la salida del controlador más los tres canales.
        $this->assertSame(
            4,
            HardwareEnergyReading::query()
                ->whereHas('hardwareEnergy', fn ($q) => $q->where('role', HardwareEnergy::ROLE_LOAD))
                ->count()
        );
    }

    #[Test]
    public function one_broken_role_does_not_drag_down_the_others(): void
    {
        // La salida de carga se desactiva desde el panel. La generación y la
        // batería de la misma petición tienen que seguir guardándose: la
        // transacción está para que no queden agregados a medias, no para
        // tirar la petición entera por un canal.
        $this->solarLoad->update(['is_active' => false]);

        $response = $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => ['voltage' => 34.5, 'amperage' => 4.2],
                'battery' => ['voltage' => 13.4, 'amperage' => 5.0],
                'loads' => [['channel' => 0, 'voltage' => 12.1, 'amperage' => 2.5]],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, $response->json('data'));
        $this->assertStringContainsString('desactivado', implode(' ', $response->json('warnings')));

        $this->assertSame(1, HardwareEnergyReading::query()->where('hardware_energy_id', $this->solarPanel->id)->count());
        $this->assertSame(1, HardwareEnergyReading::query()->where('hardware_energy_id', $this->solarBattery->id)->count());
        $this->assertSame(0, HardwareEnergyReading::query()->where('hardware_energy_id', $this->solarLoad->id)->count());
    }

    #[Test]
    public function a_payload_where_nothing_can_be_stored_fails_instead_of_pretending(): void
    {
        // Los tres roles desactivados: no hay nada que guardar. Antes esto
        // respondía 201 y el dato se perdía en silencio durante meses.
        $this->solarPanel->update(['is_active' => false]);
        $this->solarBattery->update(['is_active' => false]);
        $this->solarLoad->update(['is_active' => false]);

        $response = $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => ['voltage' => 34.5, 'amperage' => 4.2],
                'battery' => ['voltage' => 13.4, 'amperage' => 5.0],
                'loads' => [['channel' => 0, 'voltage' => 12.1, 'amperage' => 2.5]],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function the_same_channel_twice_in_one_payload_stores_both_samples(): void
    {
        // Un firmware que manda dos muestras del mismo canal en la misma
        // petición. Las dos son lecturas válidas del mismo elemento y las dos
        // cuentan en el resumen del día.
        $response = $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 3600,
            'energy' => [
                'loads' => [
                    ['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0],
                    ['channel' => 0, 'voltage' => 12.0, 'amperage' => 3.0],
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSame(2, HardwareEnergyReading::query()->where('hardware_energy_id', $this->routerChannel->id)->count());

        $resumen = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->routerChannel->id)
            ->firstOrFail();

        $this->assertSame(2, $resumen->readings_count);
        // 12 Wh + 36 Wh
        $this->assertEqualsWithDelta(48.0, (float) $resumen->energy_wh, 0.0001);
        // Y los extremos recogen las dos.
        $this->assertEqualsWithDelta(12.0, (float) $resumen->power_min, 0.0001);
        $this->assertEqualsWithDelta(36.0, (float) $resumen->power_max, 0.0001);
    }

    #[Test]
    public function a_voltage_outside_the_range_is_stored_and_reported(): void
    {
        // 400 V en un canal de 12 V huele a sensor suelto, pero **la medida se
        // guarda**. Sustituirla por la nominal, que es lo que se hacía antes,
        // borraba las medidas raras que justamente hay que poder ver: con esa
        // regla el panel solar a 0 V de noche se guardaba a 24 V y el mínimo
        // del día del panel era 24 V todos los días.
        $response = $this->subir($this->multiMonitor, [
            'hardware_device_id' => $this->multiMonitor->id,
            'duration' => 3600,
            'energy' => ['loads' => [['channel' => 0, 'voltage' => 400.0, 'amperage' => 2.0]]],
        ]);

        $response->assertStatus(201);
        $this->assertSame('measured', $response->json('data.0.sources.voltage'));
        $this->assertEqualsWithDelta(400.0, (float) $response->json('data.0.measured.voltage'), 0.0001);

        // Y se avisa, para que no pase desapercibido.
        $this->assertNotEmpty($response->json('warnings'));
        $this->assertStringContainsString('se sale del rango', implode(' ', $response->json('warnings')));
    }

    #[Test]
    public function a_panel_at_zero_volts_at_night_keeps_its_zero(): void
    {
        // El caso que motivó el cambio: de noche el panel está a 0 V y 0 A. Ese
        // 0 es una medida, no una ausencia de dato, y el mínimo del día tiene
        // que reflejarlo.
        $response = $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 3600,
            'energy' => ['generator' => ['voltage' => 0.0, 'amperage' => 0.0, 'power' => 0.0]],
        ]);

        $response->assertStatus(201);
        $this->assertSame('measured', $response->json('data.0.sources.voltage'));
        $this->assertEqualsWithDelta(0.0, (float) $response->json('data.0.measured.voltage'), 0.0001);

        $resumen = HardwareEnergyToday::query()
            ->where('hardware_energy_id', $this->solarPanel->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta(0.0, (float) $resumen->voltage_min, 0.0001);
    }

    #[Test]
    public function an_empty_loads_array_is_accepted_alongside_the_other_blocks(): void
    {
        // Un controlador sin nada conectado a la salida de carga: manda la
        // lista vacía y no debería inventarse una lectura de consumo a cero.
        $response = $this->subir($this->solarController, [
            'hardware_device_id' => $this->solarController->id,
            'duration' => 60,
            'energy' => [
                'generator' => ['voltage' => 34.5, 'amperage' => 4.2],
                'loads' => [],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(0, HardwareEnergyReading::query()->where('hardware_energy_id', $this->solarLoad->id)->count());
    }

    #[Test]
    public function a_device_token_cannot_upload_for_another_device(): void
    {
        // El token del monitor no puede escribir lecturas del controlador solar.
        $response = $this->postJson(
            $this->apiUrl('energy/readings'),
            [
                'hardware_device_id' => $this->solarController->id,
                'duration' => 60,
                'energy' => ['generator' => ['voltage' => 34.5, 'amperage' => 4.2]],
            ],
            $this->deviceHeaders($this->multiMonitor, [TokenAbilities::ENERGY_WRITE])
        );

        $response->assertStatus(422);
        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }
}
