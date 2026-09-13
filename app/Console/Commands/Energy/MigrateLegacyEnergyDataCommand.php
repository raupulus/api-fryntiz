<?php

declare(strict_types=1);

namespace App\Console\Commands\Energy;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra las lecturas del esquema viejo al unificado.
 *
 * Cuatro cosas que conviene tener presentes antes de lanzarlo:
 *
 * 1. **Empieza por un `TRUNCATE`** de las tres tablas nuevas, así que lo que
 *    haya entrado en vivo desaparece. Se lanza antes de que los aparatos
 *    empiecen a subir, no después.
 * 2. **El dispositivo de cada fila lo pone el catálogo, no la fila vieja.** Las
 *    tablas antiguas guardaban a veces el aparato *monitorizado* en vez del que
 *    mide, y copiarlo tal cual dejaba resúmenes que ninguna pantalla
 *    encontraba. El `JOIN` con `hardware_energy` además descarta por
 *    construcción las filas sin elemento asignado.
 * 3. **Las tablas viejas se solapan entre sí.** `hardware_power_loads` siguió
 *    recibiendo las lecturas del Rover mientras
 *    `hardware_power_generators_solar` ya las guardaba también: del 06 al 13 de
 *    septiembre de 2026 las mismas 1.720 lecturas están en las dos. Cada
 *    instante se inserta una sola vez.
 * 4. **Los extremos diarios del esquema viejo no se copian: se recalculan.**
 *    Los mandaba el firmware y venían mal —`power_max` era literalmente
 *    `amperage_max × 100`, hasta 1.300 W en un controlador de 20 A— y
 *    `readings_count` llegaba a 0 en 907 de 915 días. Teniendo las lecturas,
 *    salen de ellas. Lo que **no** se recalcula es la energía del día: ésa la
 *    declara el controlador y las lecturas viejas no la traen.
 */
class MigrateLegacyEnergyDataCommand extends Command
{
    protected $signature = 'energy:migrate-legacy-data
                            {--dry-run : Muestra los recuentos a migrar sin realizar escrituras}
                            {--force : Ejecutar sin solicitar confirmación interactiva}';

    protected $description = 'Migra datos de tablas legacy de energía hacia hardware_energy_readings, _today e _historical';

    public function handle(): int
    {
        $this->info('Iniciando proceso de migración de datos legacy de energía...');

        $genCount = DB::table('hardware_power_generators')->count();
        $loadCount = DB::table('hardware_power_loads')->count();
        $solarCount = Schema::hasTable('hardware_power_generators_solar')
            ? DB::table('hardware_power_generators_solar')->count()
            : 0;

        $genTodayCount = DB::table('hardware_power_generators_today')->count();
        $loadTodayCount = DB::table('hardware_power_loads_today')->count();

        $genHistCount = DB::table('hardware_power_generators_historical')->count();
        $loadHistCount = DB::table('hardware_power_loads_historical')->count();

        $this->table(
            ['Origen Legacy', 'Registros a migrar', 'Destino Unificado'],
            [
                ['hardware_power_generators', number_format($genCount), 'hardware_energy_readings'],
                ['hardware_power_loads', number_format($loadCount), 'hardware_energy_readings'],
                ['hardware_power_generators_solar', number_format($solarCount), 'hardware_energy_readings (split en panel/carga/batería)'],
                ['hardware_power_generators_today', number_format($genTodayCount), 'hardware_energy_today'],
                ['hardware_power_loads_today', number_format($loadTodayCount), 'hardware_energy_today'],
                ['hardware_power_generators_historical', number_format($genHistCount), 'hardware_energy_historical (session_index=1)'],
                ['hardware_power_loads_historical', number_format($loadHistCount), 'hardware_energy_historical (session_index=1)'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('Modo --dry-run activado: No se ha realizado ninguna modificación en la base de datos.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('¿Deseas proceder con el traspaso de datos a las tablas unificadas?')) {
            $this->warn('Operación cancelada por el usuario.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($solarCount) {
            $this->info('0/6. Vaciando tablas unificadas...');
            DB::statement('TRUNCATE TABLE hardware_energy_readings, hardware_energy_today, hardware_energy_historical RESTART IDENTITY CASCADE');

            $this->info('1/6. Traspasando lecturas de generadores y consumos...');
            $this->traspasaLecturasDeGeneradores();
            $this->traspasaLecturasDeConsumos();

            if ($solarCount > 0) {
                $this->info('2/6. Descomponiendo hardware_power_generators_solar (panel, carga y batería)...');
                $this->traspasaLecturasSolares();
            }

            $this->info('3/6. Traspasando resúmenes diarios...');
            $this->traspasaResumenesDiarios();

            if ($solarCount > 0) {
                $this->info('4/6. Resúmenes diarios de la batería desde la tabla solar...');
                $this->traspasaResumenesDiariosDeBateria();
            }

            $this->info('5/6. Traspasando acumulados históricos...');
            $this->traspasaAcumulados();

            $this->info('6/6. Recalculando contadores y extremos diarios desde las lecturas...');
            $this->recalculaResumenesDesdeLasLecturas();
        });

        $this->resumenFinal();

        return self::SUCCESS;
    }

    // ───────────────────────────── Lecturas ─────────────────────────────

    private function traspasaLecturasDeGeneradores(): void
    {
        DB::statement('
            INSERT INTO hardware_energy_readings (
                hardware_device_id, hardware_energy_id, voltage, amperage, power,
                delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                battery_voltage, battery_percentage, temperature, fan,
                charging_status, charging_status_label, light_status, light_brightness,
                is_suspicious, suspicious_reason, created_at, updated_at
            )
            SELECT
                e.hardware_device_id, l.hardware_energy_id, l.voltage, l.amperage, l.power,
                l.delta_seconds, l.energy_wh, l.energy_ah, l.energy_source, l.voltage_source,
                l.battery_voltage, l.battery_percentage, l.temperature, NULL,
                l.charging_status, l.charging_status_label, l.light_status, l.light_brightness,
                l.is_suspicious, l.suspicious_reason, COALESCE(l.read_at, l.created_at), l.updated_at
            FROM hardware_power_generators AS l
            JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
            ORDER BY l.id ASC
        ');
    }

    private function traspasaLecturasDeConsumos(): void
    {
        DB::statement('
            INSERT INTO hardware_energy_readings (
                hardware_device_id, hardware_energy_id, voltage, amperage, power,
                delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                battery_voltage, battery_percentage, temperature, fan,
                charging_status, charging_status_label, light_status, light_brightness,
                is_suspicious, suspicious_reason, created_at, updated_at
            )
            SELECT
                e.hardware_device_id, l.hardware_energy_id, l.voltage, l.amperage, l.power,
                l.delta_seconds, l.energy_wh, l.energy_ah, l.energy_source, l.voltage_source,
                l.battery_voltage, l.battery_percentage, l.temperature, l.fan,
                NULL, NULL, NULL, NULL,
                l.is_suspicious, l.suspicious_reason, COALESCE(l.read_at, l.created_at), l.updated_at
            FROM hardware_power_loads AS l
            JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
            ORDER BY l.id ASC
        ');
    }

    /**
     * Una fila de la tabla solar son **tres** medidas: el panel, la salida de
     * carga y la batería. Se reparten en tres elementos del mismo controlador.
     *
     * El `NOT EXISTS` es lo que evita duplicar: mientras la V2 escribía en esta
     * tabla, las lecturas de consumo seguían entrando además en
     * `hardware_power_loads`. Sin él, del 06 al 13 de septiembre de 2026 cada
     * lectura del consumo del Rover entraba dos veces.
     */
    private function traspasaLecturasSolares(): void
    {
        foreach ($this->controladoresSolares() as $deviceId => $elementos) {
            if (isset($elementos[HardwareEnergy::ROLE_GENERATOR])) {
                DB::insert($this->sqlLecturaSolar(
                    'voltage, amperage, power',
                    'energy_wh, energy_ah, energy_source, voltage_source',
                    'NULL, NULL, temperature, NULL,
                     charging_status, charging_status_label, light_status, light_brightness',
                ), [$elementos[HardwareEnergy::ROLE_GENERATOR], $deviceId, $elementos[HardwareEnergy::ROLE_GENERATOR]]);
            }

            if (isset($elementos[HardwareEnergy::ROLE_LOAD])) {
                DB::insert($this->sqlLecturaSolar(
                    'load_voltage, load_current, load_power',
                    "NULL, NULL, 'derived', 'measured'",
                    'NULL, NULL, temperature, load_fan,
                     NULL, NULL, NULL, NULL',
                    'AND (load_voltage IS NOT NULL OR load_current IS NOT NULL OR load_power IS NOT NULL)',
                ), [$elementos[HardwareEnergy::ROLE_LOAD], $deviceId, $elementos[HardwareEnergy::ROLE_LOAD]]);
            }

            if (isset($elementos[HardwareEnergy::ROLE_BATTERY])) {
                DB::insert($this->sqlLecturaSolar(
                    'battery_voltage, battery_current, battery_power',
                    "NULL, NULL, 'derived', 'measured'",
                    'battery_voltage, battery_percentage, battery_temperature, NULL,
                     NULL, NULL, NULL, NULL',
                    'AND (battery_voltage IS NOT NULL OR battery_percentage IS NOT NULL)',
                ), [$elementos[HardwareEnergy::ROLE_BATTERY], $deviceId, $elementos[HardwareEnergy::ROLE_BATTERY]]);
            }
        }
    }

    /**
     * El molde de las tres inserciones de arriba. Los tres `?` son, por orden:
     * el elemento destino, el dispositivo de origen y otra vez el elemento
     * —para el `NOT EXISTS`—.
     */
    private function sqlLecturaSolar(
        string $magnitudes,
        string $energia,
        string $extras,
        string $filtroExtra = '',
    ): string {
        return "
            INSERT INTO hardware_energy_readings (
                hardware_device_id, hardware_energy_id, voltage, amperage, power,
                delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                battery_voltage, battery_percentage, temperature, fan,
                charging_status, charging_status_label, light_status, light_brightness,
                is_suspicious, suspicious_reason, created_at, updated_at
            )
            SELECT
                s.hardware_device_id, ?, {$magnitudes},
                s.delta_seconds, {$energia},
                {$extras},
                s.is_suspicious, s.suspicious_reason, COALESCE(s.read_at, s.created_at), s.updated_at
            FROM hardware_power_generators_solar AS s
            WHERE s.hardware_device_id = ?
              {$filtroExtra}
              AND NOT EXISTS (
                  SELECT 1 FROM hardware_energy_readings AS r
                  WHERE r.hardware_energy_id = ?
                    AND r.created_at = COALESCE(s.read_at, s.created_at)
              )
            ORDER BY s.id ASC
        ";
    }

    // ────────────────────────── Resúmenes diarios ───────────────────────

    private function traspasaResumenesDiarios(): void
    {
        foreach (['hardware_power_generators_today', 'hardware_power_loads_today'] as $tabla) {
            $ventilador = $tabla === 'hardware_power_loads_today' ? 'l.fan_min, l.fan_max' : 'NULL, NULL';

            DB::statement("
                INSERT INTO hardware_energy_today (
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh_source, energy_ah_source,
                    energy_wh, energy_ah, voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                    fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    e.hardware_device_id, l.hardware_energy_id, l.date, 0,
                    'device', 'device',
                    COALESCE(l.energy_wh, 0), COALESCE(l.energy_ah, 0), l.voltage_min, l.voltage_max, l.amperage_min, l.amperage_max,
                    l.power_min, l.power_max, l.temperature_min, l.temperature_max,
                    l.battery_min, l.battery_max, l.battery_percentage_min, l.battery_percentage_max,
                    {$ventilador}, l.created_at, l.updated_at
                FROM {$tabla} AS l
                JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
                WHERE l.date IS NOT NULL
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ");
        }
    }

    /**
     * La batería del controlador no tenía resumen diario en el esquema viejo:
     * no había tabla para ella. Sus amperios-hora del día sí están, en la tabla
     * solar, y son los que el controlador declara haber metido en el banco.
     *
     * Sin esto, la batería se quedaba con lecturas pero sin un solo día
     * resumido, y el panel no tenía nada que enseñar de ella.
     *
     * **Se toma el último valor del día, no el mayor.** El contador diario del
     * controlador no se pone a cero a medianoche: las lecturas de las primeras
     * horas siguen arrastrando el total de ayer, así que `MAX()` devolvía el
     * día anterior y toda la serie salía corrida un día.
     *
     * Los vatios-hora se calculan —el Rover no tiene registro de vatios-hora de
     * batería—, multiplicando los amperios-hora por la tensión media del banco
     * ese día. Es la misma regla que usa la ingesta cuando falta una magnitud y
     * sobran las otras dos.
     */
    private function traspasaResumenesDiariosDeBateria(): void
    {
        foreach ($this->controladoresSolares() as $deviceId => $elementos) {
            $bateria = $elementos[HardwareEnergy::ROLE_BATTERY] ?? null;

            if ($bateria === null) {
                continue;
            }

            DB::insert("
                WITH cierre AS (
                    SELECT DISTINCT ON (s.date)
                        s.hardware_device_id, s.date, s.day_charging_amp_hours AS amperios_hora
                    FROM hardware_power_generators_solar AS s
                    WHERE s.hardware_device_id = ?
                      AND s.date IS NOT NULL
                      AND s.day_charging_amp_hours IS NOT NULL
                    ORDER BY s.date, COALESCE(s.read_at, s.created_at) DESC
                ), marco AS (
                    SELECT
                        s.date,
                        MIN(COALESCE(s.read_at, s.created_at)) AS desde,
                        MAX(COALESCE(s.read_at, s.created_at)) AS hasta,
                        AVG(NULLIF(s.battery_voltage, 0)) AS tension
                    FROM hardware_power_generators_solar AS s
                    WHERE s.hardware_device_id = ?
                      AND s.date IS NOT NULL
                    GROUP BY s.date
                )
                INSERT INTO hardware_energy_today (
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh_source, energy_ah_source, energy_wh, energy_ah,
                    created_at, updated_at
                )
                SELECT
                    cierre.hardware_device_id, ?, cierre.date, 0,
                    'derived', 'device',
                    ROUND(cierre.amperios_hora * COALESCE(marco.tension, 0), 4),
                    COALESCE(cierre.amperios_hora, 0),
                    marco.desde, marco.hasta
                FROM cierre
                JOIN marco ON marco.date = cierre.date
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ", [$deviceId, $deviceId, $bateria]);
        }
    }

    // ──────────────────────────── Acumulados ────────────────────────────

    /**
     * **El acumulado del esquema viejo es nuestro, no del aparato.**
     *
     * Lo calculaba la V1 sumando los totales diarios declarados por el
     * controlador, año tras año. No es el odómetro del controlador: el Rover
     * lleva 524.497 Wh contados así desde 2022 mientras su registro Modbus
     * marca 41.206, porque se reinició en algún momento.
     *
     * Por eso entra como `derived` y sin odómetro de partida. La primera
     * lectura que traiga uno lo adoptará como referencia y a partir de ahí se
     * sumarán sus avances, sin pisar lo de estos cuatro años ni contarlo dos
     * veces. Marcarlo como `device` era decir que ese número lo puso el
     * aparato, y con eso el acumulado se quedaba congelado.
     */
    private function traspasaAcumulados(): void
    {
        $derivado = HardwareEnergyHistorical::SOURCE_DERIVED;

        DB::statement("
            INSERT INTO hardware_energy_historical (
                hardware_device_id, hardware_energy_id, session_index, days_operating,
                readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah,
                number_battery_full_charges, number_battery_over_discharges,
                created_at, updated_at
            )
            SELECT
                e.hardware_device_id, l.hardware_energy_id, 1, l.days_operating,
                l.readings_count, '{$derivado}', '{$derivado}', COALESCE(l.energy_wh, 0), COALESCE(l.energy_ah, 0),
                l.number_battery_full_charges, l.number_battery_over_discharges,
                l.created_at, l.updated_at
            FROM hardware_power_generators_historical AS l
            JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
            ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
        ");

        DB::statement("
            INSERT INTO hardware_energy_historical (
                hardware_device_id, hardware_energy_id, session_index, days_operating,
                readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah,
                number_battery_full_charges, number_battery_over_discharges,
                voltage_min, voltage_max, amperage_min, amperage_max,
                power_min, power_max, temperature_min, temperature_max,
                battery_min, battery_max, fan_min, fan_max, created_at, updated_at
            )
            SELECT
                e.hardware_device_id, l.hardware_energy_id, 1, l.days_operating,
                l.readings_count, '{$derivado}', '{$derivado}', COALESCE(l.energy_wh, 0), COALESCE(l.energy_ah, 0),
                0, 0,
                l.voltage_min, l.voltage_max, l.amperage_min, l.amperage_max,
                l.power_min, l.power_max, l.temperature_min, l.temperature_max,
                l.battery_min, l.battery_max, l.fan_min, l.fan_max, l.created_at, l.updated_at
            FROM hardware_power_loads_historical AS l
            JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
            ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
        ");

        $this->traspasaAcumuladoDeBateria();
    }

    /**
     * Los amperios-hora que el esquema viejo guardaba en el **generador** son
     * los que entraron en la batería: el Rover no mide amperios-hora de panel.
     * En el contrato nuevo son del elemento batería, y allí se llevan.
     */
    private function traspasaAcumuladoDeBateria(): void
    {
        $derivado = HardwareEnergyHistorical::SOURCE_DERIVED;

        foreach ($this->controladoresSolares() as $deviceId => $elementos) {
            $bateria = $elementos[HardwareEnergy::ROLE_BATTERY] ?? null;
            $generador = $elementos[HardwareEnergy::ROLE_GENERATOR] ?? null;

            if ($bateria === null || $generador === null) {
                continue;
            }

            DB::insert("
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah,
                    number_battery_full_charges, number_battery_over_discharges,
                    created_at, updated_at
                )
                SELECT
                    l.hardware_device_id, ?, 1, l.days_operating,
                    0, '{$derivado}', '{$derivado}', 0, COALESCE(l.energy_ah, 0),
                    l.number_battery_full_charges, l.number_battery_over_discharges,
                    l.created_at, l.updated_at
                FROM hardware_power_generators_historical AS l
                WHERE l.hardware_energy_id = ?
                  AND l.hardware_device_id = ?
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ", [$bateria, $generador, $deviceId]);
        }
    }

    // ─────────────────── Recálculo desde las lecturas ───────────────────

    /**
     * Los contadores y los extremos de cada día salen de las lecturas, que es
     * de donde tienen que salir.
     *
     * Los del esquema viejo los mandaba el firmware y venían mal: `power_max`
     * era `amperage_max × 100` —1.300 W en un controlador de 20 A cuya lectura
     * máxima real del día fue 145 W— y `amperage_max` era la corriente de carga
     * de la batería metida en la fila del panel. `readings_count` llegaba a 0
     * en 907 de los 915 días pese a haber 400 lecturas diarias guardadas.
     *
     * **La energía del día no se toca.** Ésa sí la declara el controlador y las
     * lecturas viejas no la traen: no hay nada mejor con lo que sustituirla.
     *
     * Los días con lecturas que no tenían fila se crean aquí, sin energía: es
     * mejor un día con sus extremos y su cuenta que ningún día.
     */
    private function recalculaResumenesDesdeLasLecturas(): void
    {
        DB::statement('
            WITH resumen AS (
                SELECT
                    hardware_energy_id AS el,
                    (created_at)::date AS dia,
                    count(*) AS lecturas,
                    min(voltage) AS vmin, max(voltage) AS vmax,
                    min(amperage) AS amin, max(amperage) AS amax,
                    min(power) AS pmin, max(power) AS pmax,
                    min(temperature) AS tmin, max(temperature) AS tmax,
                    min(battery_voltage) AS bmin, max(battery_voltage) AS bmax,
                    min(battery_percentage) AS bpmin, max(battery_percentage) AS bpmax,
                    min(fan) AS fmin, max(fan) AS fmax
                FROM hardware_energy_readings
                WHERE hardware_energy_id IS NOT NULL
                GROUP BY 1, 2
            )
            UPDATE hardware_energy_today AS t
            SET readings_count = resumen.lecturas,
                voltage_min = resumen.vmin, voltage_max = resumen.vmax,
                amperage_min = resumen.amin, amperage_max = resumen.amax,
                power_min = resumen.pmin, power_max = resumen.pmax,
                temperature_min = COALESCE(resumen.tmin, t.temperature_min),
                temperature_max = COALESCE(resumen.tmax, t.temperature_max),
                battery_min = COALESCE(resumen.bmin, t.battery_min),
                battery_max = COALESCE(resumen.bmax, t.battery_max),
                battery_percentage_min = COALESCE(resumen.bpmin, t.battery_percentage_min),
                battery_percentage_max = COALESCE(resumen.bpmax, t.battery_percentage_max),
                fan_min = COALESCE(resumen.fmin, t.fan_min),
                fan_max = COALESCE(resumen.fmax, t.fan_max)
            FROM resumen
            WHERE resumen.el = t.hardware_energy_id
              AND resumen.dia = t.date
        ');

        DB::statement("
            INSERT INTO hardware_energy_today (
                hardware_device_id, hardware_energy_id, date, readings_count,
                energy_wh_source, energy_ah_source, energy_wh, energy_ah,
                voltage_min, voltage_max, amperage_min, amperage_max,
                power_min, power_max, temperature_min, temperature_max,
                battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                fan_min, fan_max, created_at, updated_at
            )
            SELECT
                e.hardware_device_id, r.hardware_energy_id, (r.created_at)::date, count(*),
                'derived', 'derived', 0, 0,
                min(r.voltage), max(r.voltage), min(r.amperage), max(r.amperage),
                min(r.power), max(r.power), min(r.temperature), max(r.temperature),
                min(r.battery_voltage), max(r.battery_voltage),
                min(r.battery_percentage), max(r.battery_percentage),
                min(r.fan), max(r.fan),
                min(r.created_at), max(r.created_at)
            FROM hardware_energy_readings AS r
            JOIN hardware_energy AS e ON e.id = r.hardware_energy_id
            GROUP BY e.hardware_device_id, r.hardware_energy_id, (r.created_at)::date
            ON CONFLICT (hardware_energy_id, date) DO NOTHING
        ");
    }

    // ─────────────────────────────── Apoyo ──────────────────────────────

    /**
     * Los elementos de cada controlador solar, por papel.
     *
     * La tabla solar guarda las tres medidas de un controlador en una sola
     * fila, así que hay que saber a qué elemento va cada una. Se pregunta al
     * catálogo en vez de poner los ids a mano: con ids fijos, el traspaso sólo
     * funcionaba en la instalación para la que se escribió.
     *
     * @return array<int, array<string, int>>
     */
    private function controladoresSolares(): array
    {
        if (! Schema::hasTable('hardware_power_generators_solar')) {
            return [];
        }

        /** @var array<int, int> $dispositivos */
        $dispositivos = DB::table('hardware_power_generators_solar')
            ->distinct()
            ->pluck('hardware_device_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $mapa = [];

        foreach ($dispositivos as $deviceId) {
            $elementos = HardwareEnergy::query()
                ->where('hardware_device_id', $deviceId)
                ->orderBy('sensor_position')
                ->get();

            foreach ($elementos as $elemento) {
                // El primero de cada papel: un controlador mide un panel, una
                // batería y una salida de carga.
                $mapa[$deviceId][(string) $elemento->role] ??= (int) $elemento->id;
            }
        }

        return $mapa;
    }

    private function resumenFinal(): void
    {
        $finalReadings = DB::table('hardware_energy_readings')->count();
        $finalToday = DB::table('hardware_energy_today')->count();
        $finalHist = DB::table('hardware_energy_historical')->count();

        $baterias = HardwareEnergy::query()
            ->where('role', HardwareEnergy::ROLE_BATTERY)
            ->pluck('id');

        $lecturasDeBateria = DB::table('hardware_energy_readings')
            ->whereIn('hardware_energy_id', $baterias)
            ->count();

        $this->newLine();
        $this->info('¡Migración completada con éxito!');
        $this->table(
            ['Tabla Unificada', 'Registros Totales Migrados'],
            [
                ['hardware_energy_readings', number_format($finalReadings)],
                ['hardware_energy_today', number_format($finalToday)],
                ['hardware_energy_historical', number_format($finalHist)],
                ['hardware_energy_readings (de baterías)', number_format($lecturasDeBateria)],
            ]
        );
    }
}
