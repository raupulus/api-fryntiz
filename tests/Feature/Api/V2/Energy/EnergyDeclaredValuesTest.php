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
 * La regla del módulo: **lo que el aparato manda se guarda; lo que no manda, se
 * calcula.**
 *
 * Suena obvio y es justo lo que se rompió. El contrato de la V1
 * (`StoreSolarChargeRequest` de la rama `main`) recibía del Renogy Rover ocho
 * acumuladores —vatios-hora y amperios-hora, del día y de por vida, de
 * generación y de consumo— y además los máximos del día que el propio
 * controlador calcula. Al reescribir el módulo, la mitad de esos campos se
 * quedaron sin sitio en el contrato nuevo: el firmware los enviaba, el servidor
 * respondía 201 y los tiraba a la basura sin un aviso.
 *
 * El daño de eso es que no se nota. Los números del panel siguen saliendo,
 * sólo que calculados a partir de muestreos cada minuto en vez de leídos del
 * contador del aparato, y nadie mira los amperios-hora hasta meses después.
 *
 * Equivalencias con los registros Modbus del Rover, por si hay que volver:
 *
 * | Campo del contrato | Registro | Elemento |
 * |---|---|---|
 * | `generator.today_energy_wh` | `0113H` generación de hoy | panel |
 * | `generator.today_energy_ah` | `0111H` Ah cargados hoy | panel |
 * | `generator.historical_energy_wh` | `011CH`-`011DH` generación de por vida | panel |
 * | `generator.historical_energy_ah` | `0118H`-`0119H` Ah cargados totales | panel |
 * | `loads[].today_energy_wh` | `0114H` consumo de hoy | salida de carga |
 * | `loads[].today_energy_ah` | `0112H` Ah descargados hoy | salida de carga |
 * | `loads[].historical_energy_wh` | `011EH`-`011FH` consumo de por vida | salida de carga |
 * | `loads[].historical_energy_ah` | `011AH`-`011BH` Ah descargados totales | salida de carga |
 * | `battery.today_voltage_min/max` | `010BH`/`010CH` tensión de batería del día | batería |
 * | `generator.today_amperage_max` | `010DH` corriente máxima de carga | panel |
 * | `loads[].today_amperage_max` | `010EH` corriente máxima de descarga | salida de carga |
 * | `generator.today_power_max` | `010FH` potencia máxima de carga | panel |
 * | `loads[].today_power_max` | `0110H` potencia máxima de descarga | salida de carga |
 */
class EnergyDeclaredValuesTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $rover;

    private HardwareEnergy $panel;

    private HardwareEnergy $bateria;

    private HardwareEnergy $consumo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->rover = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Renogy Rover']);

        // La instalación real: panel a 24 V, batería a 12 V y consumo a 12 V.
        $this->panel = HardwareEnergy::create([
            'hardware_device_id' => $this->rover->id,
            'hardware_device_monitorized_id' => $this->rover->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'voltage_min' => 0.0,
            'voltage_max' => 55.0,
            'is_active' => true,
        ]);

        $this->bateria = HardwareEnergy::create([
            'hardware_device_id' => $this->rover->id,
            'hardware_device_monitorized_id' => $this->rover->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'voltage_min' => 11.0,
            'voltage_max' => 15.0,
            'capacity_ah' => 250.0,
            'is_active' => true,
        ]);

        $this->consumo = HardwareEnergy::create([
            'hardware_device_id' => $this->rover->id,
            'hardware_device_monitorized_id' => $this->rover->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'voltage_min' => 11.0,
            'voltage_max' => 15.0,
            'is_active' => true,
        ]);
    }

    /**
     * Una subida del Rover con todo lo que el controlador sabe.
     *
     * @param  array<string, mixed>  $energy
     */
    private function subir(array $energy): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            ['hardware_device_id' => $this->rover->id, 'energy' => $energy],
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    /**
     * El payload completo del Rover, tal y como lo manda la Pico.
     *
     * @return array<string, mixed>
     */
    private function payloadCompleto(): array
    {
        return [
            'generator' => [
                'voltage' => 25.4, 'amperage' => 4.2, 'power' => 106.7,
                'temperature' => 31.0,
                'today_energy_wh' => 812.0,
                'today_energy_ah' => 33.8,
                'today_amperage_max' => 6.1,
                'today_power_max' => 148.0,
                'historical_energy_wh' => 523755.0,
                'historical_energy_ah' => 65191.0,
                'total_operating_days' => 1745,
            ],
            'battery' => [
                'voltage' => 13.4, 'amperage' => 7.9, 'power' => 105.9, 'soc' => 92,
                'today_voltage_min' => 12.1,
                'today_voltage_max' => 14.3,
                'battery_full_charges' => 1348,
                'battery_over_discharges' => 26,
                'total_operating_days' => 1745,
            ],
            'loads' => [[
                'channel' => 0,
                'voltage' => 12.9, 'amperage' => 2.1, 'power' => 27.1,
                'today_energy_wh' => 401.0,
                'today_energy_ah' => 31.1,
                'today_amperage_max' => 4.4,
                'today_power_max' => 56.0,
                'historical_energy_wh' => 450407.0,
                'historical_energy_ah' => 35778.0,
                'total_operating_days' => 1745,
            ]],
        ];
    }

    private function resumen(HardwareEnergy $elemento): HardwareEnergyToday
    {
        return HardwareEnergyToday::query()
            ->where('hardware_energy_id', $elemento->id)
            ->firstOrFail();
    }

    private function acumulado(HardwareEnergy $elemento): HardwareEnergyHistorical
    {
        return HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $elemento->id)
            ->firstOrFail();
    }

    #[Test]
    public function the_whole_rover_payload_is_accepted(): void
    {
        $this->subir($this->payloadCompleto())
            ->assertStatus(201)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function the_discharge_amp_hours_of_the_day_reach_the_load(): void
    {
        // Éste es el campo que se perdía: el Rover manda los amperios-hora
        // descargados del día y no había dónde ponerlos.
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $this->assertEqualsWithDelta(31.1, (float) $this->resumen($this->consumo)->energy_ah, 0.001);
    }

    #[Test]
    public function the_lifetime_discharge_amp_hours_reach_the_load(): void
    {
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $acumulado = $this->acumulado($this->consumo);

        $this->assertEqualsWithDelta(35778.0, (float) $acumulado->energy_ah, 0.001);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $acumulado->energy_ah_source);
    }

    #[Test]
    public function the_charge_amp_hours_reach_the_generator(): void
    {
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $this->assertEqualsWithDelta(33.8, (float) $this->resumen($this->panel)->energy_ah, 0.001);
        $this->assertEqualsWithDelta(65191.0, (float) $this->acumulado($this->panel)->energy_ah, 0.001);
    }

    #[Test]
    public function each_magnitude_records_where_it_came_from(): void
    {
        // El panel y el consumo traen odómetro de las dos magnitudes; la batería
        // no trae ninguna de las dos —el Rover no tiene registros de energía de
        // batería—, así que las suyas se calculan.
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $panel = $this->acumulado($this->panel);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $panel->energy_wh_source);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $panel->energy_ah_source);

        $bateria = $this->acumulado($this->bateria);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $bateria->energy_wh_source);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $bateria->energy_ah_source);
    }

    #[Test]
    public function a_magnitude_the_device_does_not_report_is_calculated_and_not_left_at_zero(): void
    {
        // El fallo que esto fija: con una sola marca de origen por sesión,
        // marcar el elemento como «del aparato» congelaba también la magnitud
        // que el aparato no manda y la dejaba a 0 para siempre, mientras el
        // resumen del día sí la calculaba. Dos tablas, dos respuestas distintas
        // a la misma pregunta.
        $payload = $this->payloadCompleto();

        // El generador declara los Wh pero no los Ah.
        unset($payload['generator']['today_energy_ah'], $payload['generator']['historical_energy_ah']);

        $this->subir($payload)->assertStatus(201);

        $acumulado = $this->acumulado($this->panel);

        $this->assertSame(HardwareEnergyHistorical::SOURCE_DEVICE, $acumulado->energy_wh_source);
        $this->assertSame(HardwareEnergyHistorical::SOURCE_DERIVED, $acumulado->energy_ah_source);
        $this->assertEqualsWithDelta(523755.0, (float) $acumulado->energy_wh, 0.001);
        $this->assertGreaterThan(0.0, (float) $acumulado->energy_ah, 'Los Ah que el aparato no manda se calculan.');
    }

    #[Test]
    public function the_odometer_of_one_magnitude_does_not_freeze_the_other(): void
    {
        // Dos subidas: la segunda tiene que seguir sumando los Ah calculados
        // mientras los Wh se quedan en lo que diga el odómetro.
        $payload = $this->payloadCompleto();
        unset($payload['generator']['today_energy_ah'], $payload['generator']['historical_energy_ah']);

        $this->subir($payload)->assertStatus(201);
        $primeros = (float) $this->acumulado($this->panel)->energy_ah;

        $this->subir($payload)->assertStatus(201);
        $segundos = (float) $this->acumulado($this->panel)->energy_ah;

        $this->assertGreaterThan($primeros, $segundos, 'Los Ah calculados siguen acumulando.');
        $this->assertEqualsWithDelta(523755.0, (float) $this->acumulado($this->panel)->energy_wh, 0.001);
    }

    #[Test]
    public function the_day_maximums_of_the_device_win_over_ours(): void
    {
        // El controlador ve los picos entre muestra y muestra; nosotros no.
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $resumen = $this->resumen($this->panel);

        $this->assertEqualsWithDelta(6.1, (float) $resumen->amperage_max, 0.001);
        $this->assertEqualsWithDelta(148.0, (float) $resumen->power_max, 0.001);
    }

    #[Test]
    public function a_day_maximum_of_the_device_never_narrows_what_we_measured(): void
    {
        // Si nuestra propia lectura supera el máximo que declara el aparato, el
        // bueno es el nuestro: el dato existe y no se puede recortar.
        $payload = $this->payloadCompleto();
        $payload['generator']['today_power_max'] = 10.0;

        $this->subir($payload)->assertStatus(201);

        $this->assertEqualsWithDelta(
            106.7,
            (float) $this->resumen($this->panel)->power_max,
            0.001,
            'La potencia medida en esta misma lectura es mayor que el máximo declarado.'
        );
    }

    #[Test]
    public function the_battery_voltage_range_of_the_day_comes_from_the_device(): void
    {
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $resumen = $this->resumen($this->bateria);

        $this->assertEqualsWithDelta(12.1, (float) $resumen->voltage_min, 0.001);
        $this->assertEqualsWithDelta(14.3, (float) $resumen->voltage_max, 0.001);
    }

    #[Test]
    public function the_battery_cycles_reach_the_accumulator(): void
    {
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $acumulado = $this->acumulado($this->bateria);

        $this->assertSame(1348, $acumulado->number_battery_full_charges);
        $this->assertSame(26, $acumulado->number_battery_over_discharges);
        $this->assertSame(1745, $acumulado->days_operating);
    }

    #[Test]
    public function each_element_keeps_its_own_voltage(): void
    {
        // Lo que el usuario pidió auditar: nada se fuerza a 12 V. El panel va a
        // 24 V y la batería y el consumo a 12 V, cada uno con lo suyo.
        $this->subir($this->payloadCompleto())->assertStatus(201);

        $this->assertEqualsWithDelta(25.4, (float) $this->resumen($this->panel)->voltage_max, 0.001);
        $this->assertEqualsWithDelta(12.9, (float) $this->resumen($this->consumo)->voltage_max, 0.001);
    }

    #[Test]
    public function a_deep_discharge_is_visible_instead_of_being_replaced(): void
    {
        // La batería por debajo de su tensión de 0 %: antes se sustituía por la
        // nominal y se guardaba 12 V con un 25 % de carga, así que una
        // sobredescarga real no se veía en ninguna pantalla.
        $respuesta = $this->subir([
            'battery' => ['voltage' => 10.4, 'amperage' => -5.0],
        ]);

        $respuesta->assertStatus(201);

        $this->assertEqualsWithDelta(10.4, (float) $respuesta->json('data.0.measured.voltage'), 0.001);
        $this->assertSame('measured', $respuesta->json('data.0.sources.voltage'));
        $this->assertSame(0, $respuesta->json('data.0.measured.battery_percentage'));
        $this->assertNotEmpty($respuesta->json('warnings'));
    }

    #[Test]
    public function a_discharging_battery_keeps_its_sign(): void
    {
        // La corriente de batería es neta y con signo: negativa al descargar.
        // Marcarla como sospechosa dejaría fuera media serie.
        $respuesta = $this->subir([
            'duration' => 3600,
            'battery' => ['voltage' => 12.4, 'amperage' => -2.24, 'power' => -27.78, 'soc' => 35],
        ]);

        $respuesta->assertStatus(201);

        $this->assertEqualsWithDelta(-27.78, (float) $respuesta->json('data.0.measured.power'), 0.001);
        $this->assertFalse($respuesta->json('data.0.is_suspicious'));
        $this->assertEqualsWithDelta(-2.24, (float) $this->resumen($this->bateria)->energy_ah, 0.001);
    }
}
