<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `energy:migrate-legacy-data` — el traspaso del esquema viejo al unificado.
 *
 * Se ejecuta **una vez** contra la base real, y empieza por un `TRUNCATE` de
 * las tres tablas nuevas. No había ni un test: si se lleva algo por delante o
 * deja filas a medias no hay forma de enterarse hasta mirar los datos.
 *
 * Lo que fijan estas pruebas es el comportamiento del que depende poder
 * relanzarlo sin miedo:
 *
 * - `--dry-run` no escribe nada;
 * - las filas del esquema viejo **sin elemento asignado** no pasan, porque en
 *   el esquema nuevo una lectura sin `hardware_energy_id` no se puede sumar a
 *   ningún sitio y se cuela en cualquier `sum()` que no filtre;
 * - relanzarlo deja exactamente el mismo resultado, no el doble.
 *
 * Los ids 4, 7 y 11 que el comando trae escritos a mano son los del
 * controlador Renogy de la instalación real. Aquí no se prueban: lo que se
 * prueba es el camino genérico, que es el que puede romper los datos de todos
 * los demás elementos.
 */
class MigrateLegacyEnergyDataCommandTest extends TestCase
{
    use RefreshDatabase;

    private HardwareEnergy $generador;

    private HardwareEnergy $consumo;

    /** Un aparato cualquiera, para simular una fila mal atribuida. */
    private HardwareDevice $otroAparato;

    protected function setUp(): void
    {
        parent::setUp();

        $device = HardwareDevice::create(['name' => 'Monitor legacy']);
        $this->otroAparato = HardwareDevice::create(['name' => 'Aparato medido']);

        $this->generador = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 18.0,
            'is_active' => true,
        ]);

        $this->consumo = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);

        $this->sembrarEsquemaViejo($device->id);
    }

    /**
     * Dos lecturas por tabla, una con elemento y otra sin él, más un resumen
     * diario y un acumulado de cada tipo.
     */
    private function sembrarEsquemaViejo(int $deviceId): void
    {
        $ahora = now('UTC');

        DB::table('hardware_power_generators')->insert([
            [
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $this->generador->id,
                'voltage' => 18.0, 'amperage' => 3.0, 'power' => 54.0,
                'delta_seconds' => 3600, 'energy_wh' => 54.0, 'energy_ah' => 3.0,
                'energy_source' => 'derived', 'voltage_source' => 'measured',
                'is_suspicious' => false, 'read_at' => $ahora,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
            [
                // Huérfana: sin elemento no se puede atribuir a nada.
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => null,
                'voltage' => 18.0, 'amperage' => 1.0, 'power' => 18.0,
                'delta_seconds' => 3600, 'energy_wh' => 18.0, 'energy_ah' => 1.0,
                'energy_source' => 'derived', 'voltage_source' => 'measured',
                'is_suspicious' => false, 'read_at' => $ahora,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
        ]);

        DB::table('hardware_power_loads')->insert([
            [
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $this->consumo->id,
                'voltage' => 12.0, 'amperage' => 2.0, 'power' => 24.0,
                'delta_seconds' => 3600, 'energy_wh' => 24.0, 'energy_ah' => 2.0,
                'energy_source' => 'derived', 'voltage_source' => 'measured',
                'is_suspicious' => false, 'read_at' => $ahora,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
            [
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => null,
                'voltage' => 12.0, 'amperage' => 9.0, 'power' => 108.0,
                'delta_seconds' => 3600, 'energy_wh' => 108.0, 'energy_ah' => 9.0,
                'energy_source' => 'derived', 'voltage_source' => 'measured',
                'is_suspicious' => false, 'read_at' => $ahora,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
        ]);

        DB::table('hardware_power_generators_today')->insert([
            'hardware_device_id' => $deviceId,
            'hardware_energy_id' => $this->generador->id,
            'date' => $ahora->toDateString(),
            'energy_wh' => 540.0, 'energy_ah' => 30.0, 'readings_count' => 10,
            'voltage_min' => 17.0, 'voltage_max' => 19.0,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        DB::table('hardware_power_loads_today')->insert([
            'hardware_device_id' => $deviceId,
            'hardware_energy_id' => $this->consumo->id,
            'date' => $ahora->toDateString(),
            'energy_wh' => 240.0, 'energy_ah' => 20.0, 'readings_count' => 10,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        DB::table('hardware_power_generators_historical')->insert([
            'hardware_device_id' => $deviceId,
            'hardware_energy_id' => $this->generador->id,
            'days_operating' => 120, 'readings_count' => 5000,
            'energy_wh' => 90000.0, 'energy_ah' => 5000.0,
            'number_battery_full_charges' => 40, 'number_battery_over_discharges' => 2,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);

        DB::table('hardware_power_loads_historical')->insert([
            'hardware_device_id' => $deviceId,
            'hardware_energy_id' => $this->consumo->id,
            'days_operating' => 120, 'readings_count' => 5000,
            'energy_wh' => 40000.0, 'energy_ah' => 3300.0,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ]);
    }

    #[Test]
    public function dry_run_writes_nothing(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('hardware_energy_readings')->count());
        $this->assertSame(0, DB::table('hardware_energy_today')->count());
        $this->assertSame(0, DB::table('hardware_energy_historical')->count());
    }

    #[Test]
    public function it_carries_over_the_readings_that_have_an_element(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(2, DB::table('hardware_energy_readings')->count());

        $generacion = DB::table('hardware_energy_readings')
            ->where('hardware_energy_id', $this->generador->id)
            ->first();

        $this->assertNotNull($generacion);
        $this->assertEqualsWithDelta(54.0, (float) $generacion->power, 0.001);
        $this->assertEqualsWithDelta(54.0, (float) $generacion->energy_wh, 0.001);
    }

    #[Test]
    public function readings_with_no_element_are_left_behind(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(
            0,
            DB::table('hardware_energy_readings')->whereNull('hardware_energy_id')->count(),
            'Una lectura sin elemento no se puede sumar a nada y ensucia cualquier total.'
        );
    }

    #[Test]
    public function it_carries_over_the_daily_summaries_and_the_accumulators(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(2, DB::table('hardware_energy_today')->count());
        $this->assertSame(2, DB::table('hardware_energy_historical')->count());

        $acumulado = DB::table('hardware_energy_historical')
            ->where('hardware_energy_id', $this->generador->id)
            ->first();

        $this->assertNotNull($acumulado);
        $this->assertSame(1, (int) $acumulado->session_index, 'Todo lo que venía del esquema viejo es la sesión 1.');
        $this->assertSame(120, (int) $acumulado->days_operating);
        $this->assertEqualsWithDelta(90000.0, (float) $acumulado->energy_wh, 0.001);
    }

    #[Test]
    public function every_accumulator_declares_where_each_magnitude_comes_from(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        // Sin estas dos columnas el cron no sabe si puede recalcular cada
        // magnitud, y son **dos** porque un aparato puede traer odómetro de los
        // vatios-hora y no de los amperios-hora.
        foreach (['energy_wh_source', 'energy_ah_source'] as $columna) {
            $this->assertSame(
                0,
                DB::table('hardware_energy_historical')->whereNull($columna)->count(),
                "Hay sesiones sin `{$columna}`."
            );
        }
    }

    #[Test]
    public function the_device_of_each_row_comes_from_the_catalogue(): void
    {
        // Las tablas viejas guardaban a veces el aparato monitorizado en vez
        // del que mide. Copiarlo tal cual dejaba filas que ninguna pantalla
        // encuentra, porque el panel filtra por dispositivo.
        DB::table('hardware_power_loads')
            ->where('hardware_energy_id', $this->consumo->id)
            ->update(['hardware_device_id' => $this->otroAparato->id]);

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(
            $this->consumo->hardware_device_id,
            DB::table('hardware_energy_readings')
                ->where('hardware_energy_id', $this->consumo->id)
                ->value('hardware_device_id'),
            'El dispositivo lo pone el catálogo del elemento, no la fila vieja.'
        );
    }

    #[Test]
    public function running_it_twice_leaves_the_same_thing(): void
    {
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $primera = [
            DB::table('hardware_energy_readings')->count(),
            DB::table('hardware_energy_today')->count(),
            DB::table('hardware_energy_historical')->count(),
            (float) DB::table('hardware_energy_historical')->sum('energy_wh'),
        ];

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $segunda = [
            DB::table('hardware_energy_readings')->count(),
            DB::table('hardware_energy_today')->count(),
            DB::table('hardware_energy_historical')->count(),
            (float) DB::table('hardware_energy_historical')->sum('energy_wh'),
        ];

        $this->assertSame($primera, $segunda, 'Empieza por un TRUNCATE: relanzarlo no puede duplicar nada.');
    }

    #[Test]
    public function it_wipes_what_the_new_schema_already_had(): void
    {
        // Es el filo del comando y conviene que esté escrito: lo que haya
        // entrado en vivo en las tablas nuevas desaparece al relanzarlo.
        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        DB::table('hardware_energy_readings')->insert([
            'hardware_device_id' => $this->generador->hardware_device_id,
            'hardware_energy_id' => $this->generador->id,
            'voltage' => 18.0, 'amperage' => 1.0, 'power' => 18.0,
            'energy_source' => 'derived', 'voltage_source' => 'measured',
            'is_suspicious' => false,
            'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);

        $this->assertSame(3, DB::table('hardware_energy_readings')->count());

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(
            2,
            DB::table('hardware_energy_readings')->count(),
            'La lectura que había entrado en vivo se la lleva el TRUNCATE.'
        );
    }

    /**
     * El tramo solar del comando, con los ids reales de la instalación.
     *
     * Los pasos 3a, 3b, 3c y 5c del comando llevan escritos a mano el
     * dispositivo 6 y los elementos 4, 7 y 11 —el Renogy Rover—, así que la
     * única forma de probarlos es montar esos mismos ids. Merece la pena: son
     * el tramo que descompone cada fila de `hardware_power_generators_solar` en
     * tres lecturas, y es lo que rescata la única ventana en la que esa tabla
     * dedicada fue la única que se llenó.
     */
    private function sembrarInstalacionSolarReal(): void
    {
        DB::table('hardware_devices')->insert([
            'id' => 6, 'name' => 'Controlador Solar Renogy Rover',
            'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);

        $elementos = [
            [4, HardwareEnergy::ROLE_GENERATOR, 24.0],
            [7, HardwareEnergy::ROLE_LOAD, 12.0],
            [11, HardwareEnergy::ROLE_BATTERY, 12.0],
        ];

        foreach ($elementos as [$id, $papel, $tension]) {
            DB::table('hardware_energy')->insert([
                'id' => $id,
                'hardware_device_id' => 6,
                'hardware_device_monitorized_id' => 6,
                'role' => $papel,
                'sensor_position' => 0,
                'nominal_voltage' => $tension,
                'is_active' => true,
                'auto_calculate_history' => true,
                'created_at' => now('UTC'), 'updated_at' => now('UTC'),
            ]);
        }

        DB::table('hardware_power_generators_solar')->insert([
            'hardware_device_id' => 6,
            'date' => now('UTC')->toDateString(),
            'read_at' => now('UTC'),
            'day_charging_amp_hours' => 66.0,
            'day_power_generation_wh' => 853.0,
            'voltage' => 25.4, 'amperage' => 4.2, 'power' => 106.7,
            'load_voltage' => 12.9, 'load_current' => 2.1, 'load_power' => 27.1, 'load_fan' => 0,
            'battery_voltage' => 12.4, 'battery_current' => -2.24, 'battery_power' => -27.78,
            'battery_percentage' => 35, 'battery_temperature' => 22.0,
            'temperature' => 31.0,
            'energy_source' => 'device', 'voltage_source' => 'measured',
            'is_suspicious' => false,
            'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);

        // El acumulado de por vida del que sale también el de la batería.
        DB::table('hardware_power_generators_historical')->insert([
            'hardware_device_id' => 6,
            'hardware_energy_id' => 4,
            'days_operating' => 1745, 'readings_count' => 878216,
            'energy_wh' => 523755.0, 'energy_ah' => 65191.0,
            'number_battery_full_charges' => 1348, 'number_battery_over_discharges' => 26,
            'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
    }

    #[Test]
    public function each_solar_row_becomes_three_readings(): void
    {
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        // El sembrado sólo trae el acumulado del generador; de ahí sale también
        // el de la batería.
        foreach ([4, 11] as $elemento) {
            $this->assertSame(
                1,
                DB::table('hardware_energy_readings')->where('hardware_energy_id', $elemento)->count(),
                "El elemento {$elemento} tiene que recibir su parte de la fila solar."
            );
        }
    }

    #[Test]
    public function the_solar_split_keeps_each_voltage_where_it_belongs(): void
    {
        // Lo que hay que auditar de verdad: nada se fuerza a 12 V. El panel va a
        // 25,4 V, el consumo a 12,9 y la batería a 12,4.
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $tension = static fn (int $elemento): float => (float) DB::table('hardware_energy_readings')
            ->where('hardware_energy_id', $elemento)
            ->value('voltage');

        $this->assertEqualsWithDelta(25.4, $tension(4), 0.001);
        $this->assertEqualsWithDelta(12.9, $tension(7), 0.001);
        $this->assertEqualsWithDelta(12.4, $tension(11), 0.001);
    }

    #[Test]
    public function the_battery_current_and_power_keep_their_sign(): void
    {
        // `battery_current` y `battery_power` van con signo: negativos mientras
        // descarga. Son los dos campos que llevaban tiempo en duda.
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $bateria = DB::table('hardware_energy_readings')->where('hardware_energy_id', 11)->first();

        $this->assertEqualsWithDelta(-2.24, (float) $bateria->amperage, 0.001);
        $this->assertEqualsWithDelta(-27.78, (float) $bateria->power, 0.001);
        $this->assertSame(35, (int) $bateria->battery_percentage);
    }

    #[Test]
    public function the_battery_gets_its_own_lifetime_row(): void
    {
        // El elemento 11 no tiene tabla histórica propia en el esquema viejo:
        // sus amperios-hora y sus ciclos salen del histórico del generador.
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $bateria = DB::table('hardware_energy_historical')->where('hardware_energy_id', 11)->first();

        $this->assertNotNull($bateria, 'La batería se quedaba sin acumulado de por vida.');
        $this->assertEqualsWithDelta(65191.0, (float) $bateria->energy_ah, 0.001);
        $this->assertSame(1348, (int) $bateria->number_battery_full_charges);
        $this->assertSame(26, (int) $bateria->number_battery_over_discharges);
    }

    #[Test]
    public function lo_que_viene_del_esquema_viejo_no_es_el_odometro_del_aparato(): void
    {
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        // **El acumulado del esquema viejo lo calculábamos nosotros.** La V1
        // sumaba los totales diarios que declaraba el controlador, año tras
        // año; no es el registro Modbus del Rover. Se nota en los números: el
        // panel llevaba 524.497 Wh contados así mientras el registro del
        // aparato marcaba 41.206, porque el controlador se reinició en algún
        // momento de esos cuatro años.
        //
        // Marcarlo como `device` era afirmar que ese número lo puso el aparato,
        // y con esa mentira pasaban dos cosas: el acumulado se congelaba —se
        // guardaba con `max()` y el odómetro nunca lo alcanzaría— y la primera
        // subida parecía un reinicio, porque el odómetro venía muy por debajo.
        // El 13/09/2026 partió en dos el histórico del panel, el consumo y la
        // batería en producción.
        // El sembrado sólo trae el acumulado del generador; de ahí sale también
        // el de la batería.
        foreach ([4, 11] as $elemento) {
            $fila = DB::table('hardware_energy_historical')->where('hardware_energy_id', $elemento)->first();

            $this->assertNotNull($fila);
            $this->assertSame('derived', $fila->energy_wh_source, "Elemento {$elemento}: los Wh los sumamos nosotros.");
            $this->assertSame('derived', $fila->energy_ah_source, "Elemento {$elemento}: los Ah los sumamos nosotros.");
            $this->assertNull($fila->energy_wh_device_total, "Elemento {$elemento}: el aparato aún no ha declarado odómetro.");
            $this->assertNull($fila->energy_ah_device_total, "Elemento {$elemento}: el aparato aún no ha declarado odómetro.");
        }
    }

    // ── Lo que el esquema viejo traía mal ─────────────────────────────────

    #[Test]
    public function las_lecturas_que_estaban_en_dos_tablas_no_se_duplican(): void
    {
        // `hardware_power_loads` siguió recibiendo las lecturas del Rover
        // mientras `hardware_power_generators_solar` ya las guardaba también.
        // Del 06 al 13 de septiembre de 2026 las mismas 1.720 lecturas estaban
        // en las dos tablas, y el traspaso las metía dos veces.
        $this->sembrarInstalacionSolarReal();

        $instante = Carbon::parse('2026-09-10 12:05:13');

        DB::table('hardware_power_loads')->insert([
            'hardware_device_id' => 6,
            'hardware_energy_id' => 7,
            'voltage' => 14.1, 'amperage' => 2.0, 'power' => 28.0,
            'read_at' => $instante, 'created_at' => $instante, 'updated_at' => $instante,
        ]);

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $this->assertSame(
            1,
            DB::table('hardware_energy_readings')
                ->where('hardware_energy_id', 7)
                ->where('created_at', $instante)
                ->count(),
            'La misma lectura estaba en las dos tablas viejas y entró dos veces.'
        );
    }

    #[Test]
    public function los_contadores_y_los_extremos_del_dia_salen_de_las_lecturas(): void
    {
        // El esquema viejo los recibía del firmware y venían mal: `power_max`
        // era literalmente `amperage_max × 100` —1.090 W en un controlador cuya
        // lectura máxima real de ese día fueron 145 W— y `readings_count`
        // llegaba a 0 pese a haber cientos de lecturas guardadas.
        $dia = Carbon::parse('2026-09-04');

        DB::table('hardware_power_generators')->insert([
            [
                'hardware_device_id' => $this->generador->hardware_device_id,
                'hardware_energy_id' => $this->generador->id,
                'voltage' => 35.5, 'amperage' => 4.8, 'power' => 145.0,
                'read_at' => $dia->copy()->setTime(12, 0),
                'created_at' => $dia->copy()->setTime(12, 0),
                'updated_at' => $dia->copy()->setTime(12, 0),
            ],
            [
                'hardware_device_id' => $this->generador->hardware_device_id,
                'hardware_energy_id' => $this->generador->id,
                'voltage' => 30.0, 'amperage' => 2.0, 'power' => 60.0,
                'read_at' => $dia->copy()->setTime(13, 0),
                'created_at' => $dia->copy()->setTime(13, 0),
                'updated_at' => $dia->copy()->setTime(13, 0),
            ],
        ]);

        DB::table('hardware_power_generators_today')->insert([
            'hardware_device_id' => $this->generador->hardware_device_id,
            'hardware_energy_id' => $this->generador->id,
            'date' => $dia->toDateString(),
            'energy_wh' => 697.0,
            'readings_count' => 0,
            'amperage_max' => 10.9,
            'power_max' => 1090.0,
            'created_at' => $dia, 'updated_at' => $dia,
        ]);

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $resumen = DB::table('hardware_energy_today')
            ->where('hardware_energy_id', $this->generador->id)
            ->where('date', $dia->toDateString())
            ->first();

        $this->assertNotNull($resumen);
        $this->assertSame(2, (int) $resumen->readings_count, 'Las lecturas están: hay que contarlas.');
        $this->assertEqualsWithDelta(145.0, (float) $resumen->power_max, 0.001, 'El pico sale de las lecturas, no del firmware.');
        $this->assertEqualsWithDelta(4.8, (float) $resumen->amperage_max, 0.001);
        $this->assertEqualsWithDelta(30.0, (float) $resumen->voltage_min, 0.001);

        // La energía del día sí la declara el controlador y no se toca: las
        // lecturas viejas no la traen, no hay nada mejor con lo que sustituirla.
        $this->assertEqualsWithDelta(697.0, (float) $resumen->energy_wh, 0.001);
        $this->assertSame('device', $resumen->energy_wh_source);
    }

    #[Test]
    public function la_bateria_del_controlador_se_queda_con_sus_dias(): void
    {
        // En el esquema viejo no había tabla de resúmenes diarios de batería, y
        // la batería acababa con lecturas pero sin un solo día resumido: el
        // panel no tenía nada que enseñar de ella.
        $this->sembrarInstalacionSolarReal();

        $this->artisan('energy:migrate-legacy-data', ['--force' => true])->assertExitCode(0);

        $dias = DB::table('hardware_energy_today')->where('hardware_energy_id', 11)->get();

        $this->assertTrue($dias->isNotEmpty(), 'La batería se quedaba sin resúmenes diarios.');

        foreach ($dias as $dia) {
            $this->assertSame('device', $dia->energy_ah_source, 'Los amperios-hora los declara el controlador.');
            $this->assertGreaterThan(0, (int) $dia->readings_count, 'Y sus lecturas se cuentan.');
        }
    }
}
