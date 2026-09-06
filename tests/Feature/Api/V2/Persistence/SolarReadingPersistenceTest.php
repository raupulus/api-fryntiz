<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorSolar;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;
use Tests\Traits\AssertsPersistence;

/**
 * POST /api/v2/energy/solar-readings — el endpoint del Renogy Rover.
 *
 * Era el que más datos perdía de todo el proyecto y sólo tenía dos tests: uno de
 * 401 y otro de 422. Ninguno podía detectarlo, porque el endpoint respondía 201
 * igualmente (hallazgos H2, R-4 y N279).
 *
 * El payload es **el vocabulario real del firmware**, no el de la API: el Rover
 * manda `solar_voltage`, `controller_temperature` y los bloques `today_*` e
 * `historical_*`. La traducción la hace `StoreSolarReadingRequest`, que es el
 * único sitio del proyecto donde se traduce —el Rover es un aparato comercial y
 * no se le va a cambiar el protocolo—.
 *
 * Probar con los nombres de la API en vez de con los del firmware sería probar
 * algo que ningún aparato manda.
 */
class SolarReadingPersistenceTest extends ApiTestCase
{
    use AssertsPersistence;

    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover de pruebas',
        ]);
    }

    /**
     * Lo que manda de verdad el firmware, con valores de una instalación de 12 V.
     *
     * @return array<string,mixed>
     */
    private function fullPayload(): array
    {
        return [
            'hardware_device_id' => $this->device->id,
            'read_at' => '2026-08-24 11:30:00',

            'hardware' => 'Renogy Rover Li 40A',
            'version' => 'v1.2.3',
            'serial_number' => 'RNG-0001-TEST',
            'battery_type' => 'lifepo4',

            'battery_voltage' => 13.4,
            // El Rover lo llama `battery_soc`.
            'battery_soc' => 87,

            // El Rover lo llama así; la columna es `temperature`.
            'controller_temperature' => 24.5,

            'load_voltage' => 12.9,
            'load_current' => 1.85,
            'load_power' => 23.9,

            // El Rover llama `solar_*` al panel; las columnas son las heredadas
            // del generador genérico: `voltage`, `amperage` y `power`.
            'solar_voltage' => 18.7,
            'solar_current' => 2.4,
            'solar_power' => 44.9,

            // Estadísticas del día, que antes se perdían enteras.
            'today_max_charging_current' => 12.4,
            'today_charging_amp_hours' => 31.2,
            'today_power_generation' => 402.5,

            // Acumulado del controlador, que también se perdía entero.
            'historical_total_days_operating' => 412,
            'historical_total_number_battery_full_charges' => 96,
            'historical_cumulative_power_generation' => 154_320.75,
        ];
    }

    /**
     * Lo que debe acabar en la fila, con los nombres de las columnas.
     *
     * @return array<string,mixed>
     */
    private function expectedColumns(): array
    {
        return [
            'hardware_device_id' => $this->device->id,
            'hardware' => 'Renogy Rover Li 40A',
            'version' => 'v1.2.3',
            'serial_number' => 'RNG-0001-TEST',
            'battery_type' => 'lifepo4',
            'battery_voltage' => 13.4,
            'battery_percentage' => 87,
            'temperature' => 24.5,
            'load_voltage' => 12.9,
            'load_current' => 1.85,
            'load_power' => 23.9,
            'voltage' => 18.7,
            'amperage' => 2.4,
            'power' => 44.9,
            'day_charging_current_max' => 12.4,
            'day_charging_amp_hours' => 31.2,
            'day_power_generation_wh' => 402.5,
            'total_operating_days' => 412,
            'total_battery_full_charges' => 96,
            'total_power_generation_wh' => 154_320.75,
        ];
    }

    private function send(array $payload): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/solar-readings'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    #[Test]
    public function the_fields_the_rover_sends_reach_the_database(): void
    {
        $response = $this->send($this->fullPayload());

        $this->assertSuccessResponse($response, 201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertNotNull($row, 'La API respondió 201 y no hay ninguna fila guardada.');

        $this->assertPersisted($row, $this->expectedColumns());
    }

    /**
     * Las fechas se sintetizan si el firmware no las manda. Es lo que evita que
     * el Rover reciba un 422 por no mandar `date` ni `read_at`.
     */
    #[Test]
    public function dates_are_synthesised_when_the_firmware_does_not_send_them(): void
    {
        $payload = $this->fullPayload();
        unset($payload['read_at']);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertNotNull($row?->read_at, 'Sin `read_at` en la petición, la fila se guardó sin fecha de lectura.');
        $this->assertNotNull($row?->date, 'Sin `date` en la petición, la fila se guardó sin fecha.');
    }

    /**
     * La otra mitad de la traducción: un campo que el firmware NO manda tiene
     * que quedar NULL, no 0. V1 casteaba a la brava (`(float) $this->campo`) y
     * convertía «no tengo dato» en un cero que estropea medias y totales.
     */
    #[Test]
    public function a_field_the_firmware_does_not_send_stays_null_and_not_zero(): void
    {
        $payload = $this->fullPayload();
        unset($payload['solar_power'], $payload['battery_soc']);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertNull($row->battery_percentage, 'Un campo ausente se guardó como 0.');
    }

    #[Test]
    public function a_real_zero_is_stored_as_zero_and_not_as_null(): void
    {
        // De noche el panel da 0 W. Es un dato, no una ausencia de dato: si se
        // guarda NULL, las medias y los totales salen mal.
        $payload = array_merge($this->fullPayload(), [
            'solar_voltage' => 0.0,
            'solar_current' => 0.0,
            'solar_power' => 0.0,
        ]);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertNotNull($row->power, 'Un 0 real de generación nocturna se guardó como NULL.');
        $this->assertEqualsWithDelta(0.0, (float) $row->power, 0.0001);
    }

    #[Test]
    public function the_reading_is_linked_to_the_right_device(): void
    {
        $this->send($this->fullPayload())->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertSame(
            $this->device->id,
            $row->hardware_device_id,
            'La lectura no quedó ligada al dispositivo: sin esto no se sabe de qué instalación es.'
        );
    }

    /**
     * El acumulado del controlador sólo puede subir. Si baja, el aparato se ha
     * reseteado: esa lectura abre **fila nueva** y la anterior se conserva. Sin
     * esto, un reset borra el acumulado de años.
     */
    #[Test]
    public function a_controller_restart_does_not_wipe_the_previous_totals(): void
    {
        $this->send($this->fullPayload())->assertStatus(201);

        $trasElReinicio = array_merge($this->fullPayload(), [
            'read_at' => '2026-08-24 11:35:00',
            'historical_total_days_operating' => 1,
            'historical_cumulative_power_generation' => 0.4,
        ]);

        $response = $this->send($trasElReinicio);
        $response->assertStatus(201);

        $this->assertSame(
            2,
            HardwarePowerGeneratorSolar::query()->count(),
            'El reinicio machacó la lectura anterior en vez de abrir fila nueva.'
        );

        $this->assertNotEmpty(
            $response->json('warnings'),
            'El reinicio del controlador no avisó en la respuesta.'
        );

        $this->assertSame(
            412,
            HardwarePowerGeneratorSolar::query()->orderBy('id')->first()->total_operating_days,
            'El acumulado de antes del reinicio se ha perdido.'
        );
    }

    #[Test]
    public function the_response_returns_stored_values_and_not_nulls(): void
    {
        // El Resource declaraba 14 campos y 7 salían siempre nulos (R-4):
        // `device_id` y `load_amperage` no existían como atributo, y otros
        // eran columnas que el $fillable no dejaba escribir.
        $response = $this->send($this->fullPayload());

        $response->assertStatus(201);

        $response->assertJsonPath('data.hardware_device_id', $this->device->id);

        foreach ([
            'controller.serial_number', 'controller.temperature',
            'battery.type', 'battery.voltage', 'battery.percentage',
            'load.voltage', 'load.current', 'load.power',
            'generation.voltage', 'generation.amperage', 'generation.power',
            'day.charging_amp_hours', 'day.power_generation_wh',
            'total.operating_days', 'total.power_generation_wh',
        ] as $field) {
            $this->assertNotNull(
                $response->json("data.{$field}"),
                "El Resource devuelve `{$field}` a NULL: el cliente no puede leer lo que acaba de escribir."
            );
        }
    }

    /**
     * AD-Q04 (auditoría de datos 2026-09-02): un Rover real en producción mandó
     * `power` hasta 82 W distinto de V×A en un puñado de lecturas — un hipo del
     * firmware, no ruido de redondeo. Con un elemento generador dado de alta,
     * `power` se sobrescribe siempre con V×A (D115); lo que hay que comprobar
     * es que la potencia que mandó el aparato, antes de perderse, deja una
     * marca en vez de desaparecer sin más.
     */
    #[Test]
    public function a_power_reading_far_from_v_times_a_is_flagged_but_not_discarded(): void
    {
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $payload = array_merge($this->fullPayload(), [
            'solar_voltage' => 20.0,
            'solar_current' => 5.0,
            // V×A da 100 W; el aparato dice 10 W: 90 W de diferencia, muy por
            // encima del umbral de 20 W.
            'solar_power' => 10.0,
        ]);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertNotNull($row, 'La lectura sospechosa se descartó en vez de marcarse.');
        $this->assertTrue((bool) $row->is_suspicious, 'La potencia inconsistente con V×A no se marcó.');
        $this->assertNotNull($row->suspicious_reason);

        // Si lo manda el aparato, se guarda lo que manda el aparato. La
        // discrepancia con V×A se refleja marcando la lectura, no sustituyendo
        // el dato medido por uno calculado.
        $this->assertEqualsWithDelta(10.0, (float) $row->power, 0.01, 'Se guardó el derivado V×A en vez de la potencia que midió el aparato.');
    }

    #[Test]
    public function la_potencia_del_aparato_se_conserva_cuando_no_manda_corriente(): void
    {
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $payload = array_merge($this->fullPayload(), [
            'solar_voltage' => 18.0,
            'solar_power' => 58.9,
        ]);
        unset($payload['solar_current'], $payload['pv_current'], $payload['amperage']);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        // Sin corriente no hay V×A que valga: antes se machacaba con null y se
        // perdía el único dato de potencia de la lectura.
        $this->assertEqualsWithDelta(58.9, (float) $row->power, 0.01, 'La potencia del aparato se perdió al no venir la corriente.');
        $this->assertNull($row->amperage);
        $this->assertFalse((bool) $row->is_suspicious, 'No hay nada con lo que comparar: no debe marcarse.');
    }

    #[Test]
    public function a_small_power_deviation_from_v_times_a_is_not_flagged(): void
    {
        // Ruido normal de muestreo: V, A y P no se leen en el mismo instante.
        // No debe dispararse con cualquier diferencia, solo con una gorda.
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $payload = array_merge($this->fullPayload(), [
            'solar_voltage' => 20.0,
            'solar_current' => 5.0,
            // V×A da 100 W; el aparato dice 98 W: 2 W de diferencia.
            'solar_power' => 98.0,
        ]);

        $this->send($payload)->assertStatus(201);

        $row = HardwarePowerGeneratorSolar::query()->latest('id')->first();

        $this->assertFalse((bool) $row->is_suspicious, 'Una diferencia de 2 W (ruido normal) se marcó como sospechosa.');
    }

    /**
     * Una subida del controlador tiene que llenar las cuatro tablas de resumen,
     * como hacía la V1.
     *
     * Guardaba sólo la fila cruda, así que `hardware_power_generators_today`,
     * `_historical`, `hardware_power_loads`, `_today` y `_historical` llevaban
     * vacías desde la V2 y el panel de energía no tenía de dónde leer.
     */
    #[Test]
    public function una_lectura_del_controlador_llena_los_resumenes_de_generacion_y_consumo(): void
    {
        $generador = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $consumo = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Salida de carga del Rover',
            'role' => HardwareEnergy::ROLE_LOAD,
            'is_generator' => false,
            'sensor_position' => 1,
            'nominal_voltage' => 12.0,
        ]);

        $payload = array_merge($this->fullPayload(), [
            'today_discharging_amp_hours' => 18.4,
            'today_power_consumption' => 221.5,
            'historical_total_discharging_amp_hours' => 7608.0,
            'historical_cumulative_power_consumption' => 5385.0,
        ]);

        $this->send($payload)->assertStatus(201);

        // ── Generación ──
        $genHoy = HardwarePowerGeneratorToday::query()
            ->where('hardware_energy_id', $generador->id)->first();

        $this->assertNotNull($genHoy, 'No se creó el resumen del día de generación.');
        // El acumulado del día lo manda el aparato: se toma tal cual, no se suma.
        $this->assertEqualsWithDelta(402.5, (float) $genHoy->energy_wh, 0.01);
        $this->assertEqualsWithDelta(31.2, (float) $genHoy->energy_ah, 0.01);

        $genTotal = HardwarePowerGeneratorHistorical::query()
            ->where('hardware_energy_id', $generador->id)->first();

        $this->assertNotNull($genTotal, 'No se creó el acumulado de generación.');
        // El total del aparato cubre años anteriores a estas tablas: manda él.
        $this->assertEqualsWithDelta(154_320.75, (float) $genTotal->energy_wh, 0.01);
        $this->assertSame(412, (int) $genTotal->days_operating);

        // ── Consumo de la salida de carga ──
        $lectura = HardwarePowerLoad::query()
            ->where('hardware_energy_id', $consumo->id)->first();

        $this->assertNotNull($lectura, 'La salida de carga del controlador no se guardó como consumo.');
        $this->assertEqualsWithDelta(12.9, (float) $lectura->voltage, 0.01);
        $this->assertEqualsWithDelta(1.85, (float) $lectura->amperage, 0.01);
        $this->assertEqualsWithDelta(23.9, (float) $lectura->power, 0.01);

        $loadHoy = HardwarePowerLoadToday::query()
            ->where('hardware_energy_id', $consumo->id)->first();

        $this->assertNotNull($loadHoy, 'No se creó el resumen del día de consumo.');
        $this->assertEqualsWithDelta(221.5, (float) $loadHoy->energy_wh, 0.01);
        $this->assertEqualsWithDelta(18.4, (float) $loadHoy->energy_ah, 0.01);

        $loadTotal = HardwarePowerLoadHistorical::query()
            ->where('hardware_energy_id', $consumo->id)->first();

        $this->assertNotNull($loadTotal, 'No se creó el acumulado de consumo.');
        $this->assertEqualsWithDelta(5385.0, (float) $loadTotal->energy_wh, 0.01);
    }

    /**
     * Dos lecturas del mismo día no duplican el acumulado del aparato.
     */
    #[Test]
    public function el_acumulado_del_dia_no_se_suma_dos_veces(): void
    {
        $generador = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $this->send($this->fullPayload())->assertStatus(201);
        $this->send(array_merge($this->fullPayload(), [
            'read_at' => '2026-08-24 11:35:00',
            'today_power_generation' => 415.0,
        ]))->assertStatus(201);

        $genHoy = HardwarePowerGeneratorToday::query()
            ->where('hardware_energy_id', $generador->id)->first();

        // El aparato dice 415 Wh en total del día, no 402,5 + 415.
        $this->assertEqualsWithDelta(415.0, (float) $genHoy->energy_wh, 0.01);
        $this->assertSame(2, (int) $genHoy->readings_count);
    }

    /**
     * Un reinicio del controlador no puede borrar el acumulado de años.
     *
     * El Rover de producción tiene 66.388 Wh guardados y hoy dice 36.087: se
     * reinició en algún momento y volvió a contar desde cero. Escribir su
     * «total» encima tiraría treinta megavatios-hora de histórico. La V1 ya
     * tenía esta regla (`($power > $this->power) ? $power : $this->power`).
     */
    #[Test]
    public function el_acumulado_nunca_baja_aunque_el_aparato_se_haya_reiniciado(): void
    {
        $generador = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        HardwarePowerGeneratorHistorical::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $generador->id,
            'energy_wh' => 66_388.0,
            'energy_ah' => 65_191.0,
            'days_operating' => 1738,
            'read_at' => now()->subDay(),
        ]);

        // El aparato manda un total mucho menor: ha vuelto a contar.
        $this->send(array_merge($this->fullPayload(), [
            'historical_cumulative_power_generation' => 36_087.0,
            'historical_total_charging_amp_hours' => 11_973.0,
            'historical_total_days_operating' => 12,
        ]))->assertStatus(201);

        $total = HardwarePowerGeneratorHistorical::query()
            ->where('hardware_energy_id', $generador->id)->first();

        $this->assertEqualsWithDelta(66_388.0, (float) $total->energy_wh, 0.01, 'El reinicio del controlador borró el acumulado.');
        $this->assertEqualsWithDelta(65_191.0, (float) $total->energy_ah, 0.01);
        $this->assertSame(1738, (int) $total->days_operating);
    }

    /**
     * Sin elemento de consumo dado de alta, la salida de carga no se pierde en
     * silencio: se avisa.
     */
    #[Test]
    public function avisa_si_hay_consumo_y_no_hay_elemento_donde_guardarlo(): void
    {
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Panel del Rover',
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'is_generator' => true,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
        ]);

        $respuesta = $this->send($this->fullPayload());

        $respuesta->assertStatus(201);
        $this->assertNotEmpty($respuesta->json('warnings'), 'No avisó de que el consumo no tiene dónde guardarse.');
    }

    #[Test]
    public function cannot_write_on_someone_elses_device(): void
    {
        $other = $this->createAuthenticatedUser(3);
        $suDispositivo = HardwareDevice::create([
            'user_id' => $other->id,
            'name' => 'Dispositivo ajeno',
        ]);

        $response = $this->send(array_merge($this->fullPayload(), [
            'hardware_device_id' => $suDispositivo->id,
        ]));

        $this->assertErrorResponse($response, 422);
        $this->assertSame(0, HardwarePowerGeneratorSolar::query()->count());
    }
}
