<?php

declare(strict_types=1);

namespace App\Console\Commands\Energy;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra las lecturas del esquema viejo al unificado.
 *
 * Dos cosas que conviene tener presentes antes de lanzarlo:
 *
 * 1. **Empieza por un `TRUNCATE`** de las tres tablas nuevas, así que lo que
 *    haya entrado en vivo desaparece. Se lanza antes de que los aparatos
 *    empiecen a subir, no después.
 * 2. **El dispositivo de cada fila lo pone el catálogo, no la fila vieja.** Las
 *    tablas antiguas guardaban a veces el aparato *monitorizado* en vez del que
 *    mide —en la base real pasa con el elemento 2, cuyas filas apuntan al
 *    dispositivo 4 cuando quien mide es el 12—, y copiarlo tal cual dejaba
 *    resúmenes que ninguna pantalla encontraba. El `JOIN` con `hardware_energy`
 *    además descarta por construcción las filas sin elemento asignado, que en
 *    el esquema nuevo no se pueden sumar a nada.
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
            $this->info('0/5. Vaciando tablas unificadas...');
            DB::statement('TRUNCATE TABLE hardware_energy_readings, hardware_energy_today, hardware_energy_historical RESTART IDENTITY CASCADE');

            $this->info('1/5. Traspasando lecturas de generadores (hardware_power_generators)...');
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

            $this->info('2/5. Traspasando lecturas de consumos (hardware_power_loads)...');
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

            if ($solarCount > 0) {
                $this->info('3/5. Descomponiendo atómicamente hardware_power_generators_solar (paneles, carga y batería)...');

                // 3a. Parte generación (elemento #4 panel FV del dispositivo #6)
                DB::statement('
                    INSERT INTO hardware_energy_readings (
                        hardware_device_id, hardware_energy_id, voltage, amperage, power,
                        delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                        battery_voltage, battery_percentage, temperature, fan,
                        charging_status, charging_status_label, light_status, light_brightness,
                        is_suspicious, suspicious_reason, created_at, updated_at
                    )
                    SELECT
                        hardware_device_id, 4, voltage, amperage, power,
                        delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                        NULL, NULL, temperature, NULL,
                        charging_status, charging_status_label, light_status, light_brightness,
                        is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                    FROM hardware_power_generators_solar
                    WHERE hardware_device_id = 6
                    ORDER BY id ASC
                ');

                // 3b. Parte consumo (elemento #7 salida DC del dispositivo #6)
                DB::statement("
                    INSERT INTO hardware_energy_readings (
                        hardware_device_id, hardware_energy_id, voltage, amperage, power,
                        delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                        battery_voltage, battery_percentage, temperature, fan,
                        charging_status, charging_status_label, light_status, light_brightness,
                        is_suspicious, suspicious_reason, created_at, updated_at
                    )
                    SELECT
                        hardware_device_id, 7, load_voltage, load_current, load_power,
                        delta_seconds, NULL, NULL, 'derived', 'measured',
                        NULL, NULL, temperature, load_fan,
                        NULL, NULL, NULL, NULL,
                        is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                    FROM hardware_power_generators_solar
                    WHERE hardware_device_id = 6
                      AND (load_voltage IS NOT NULL OR load_current IS NOT NULL OR load_power IS NOT NULL)
                    ORDER BY id ASC
                ");

                // 3c. Parte batería (elemento #11 banco LiFePO4 del dispositivo #6)
                DB::statement("
                    INSERT INTO hardware_energy_readings (
                        hardware_device_id, hardware_energy_id, voltage, amperage, power,
                        delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                        battery_voltage, battery_percentage, temperature, fan,
                        charging_status, charging_status_label, light_status, light_brightness,
                        is_suspicious, suspicious_reason, created_at, updated_at
                    )
                    SELECT
                        hardware_device_id, 11, battery_voltage, battery_current, battery_power,
                        delta_seconds, NULL, NULL, 'derived', 'measured',
                        battery_voltage, battery_percentage, battery_temperature, NULL,
                        NULL, NULL, NULL, NULL,
                        is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                    FROM hardware_power_generators_solar
                    WHERE hardware_device_id = 6
                      AND (battery_voltage IS NOT NULL OR battery_percentage IS NOT NULL)
                    ORDER BY id ASC
                ");
            }

            $this->info('4/5. Traspasando resúmenes diarios (hardware_energy_today)...');
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
                    e.hardware_device_id, l.hardware_energy_id, l.date, l.readings_count,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    l.energy_wh, l.energy_ah, l.voltage_min, l.voltage_max, l.amperage_min, l.amperage_max,
                    l.power_min, l.power_max, l.temperature_min, l.temperature_max,
                    l.battery_min, l.battery_max, l.battery_percentage_min, l.battery_percentage_max,
                    NULL, NULL, l.created_at, l.updated_at
                FROM hardware_power_generators_today AS l
                JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ");

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
                    e.hardware_device_id, l.hardware_energy_id, l.date, l.readings_count,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    l.energy_wh, l.energy_ah, l.voltage_min, l.voltage_max, l.amperage_min, l.amperage_max,
                    l.power_min, l.power_max, l.temperature_min, l.temperature_max,
                    l.battery_min, l.battery_max, l.battery_percentage_min, l.battery_percentage_max,
                    l.fan_min, l.fan_max, l.created_at, l.updated_at
                FROM hardware_power_loads_today AS l
                JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ");

            $this->info('5/5. Traspasando acumulados históricos (hardware_energy_historical)...');
            DB::statement("
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges, voltage_min, voltage_max, amperage_min,
                    amperage_max, power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    e.hardware_device_id, l.hardware_energy_id, 1, l.days_operating,
                    l.readings_count,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    -- Los amperios-hora del esquema viejo son los **cargados a la
                    -- batería**, que en el contrato nuevo son del elemento
                    -- batería, no del generador. Aquí se conserva el valor para
                    -- no perderlo, pero marcado como calculado: el Rover no mide
                    -- los amperios-hora del panel.
                    'derived',
                    l.energy_wh, l.energy_ah, l.number_battery_full_charges, l.number_battery_over_discharges,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, l.created_at, l.updated_at
                FROM hardware_power_generators_historical AS l
                JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ");

            DB::statement("
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges, voltage_min, voltage_max, amperage_min,
                    amperage_max, power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    e.hardware_device_id, l.hardware_energy_id, 1, l.days_operating,
                    l.readings_count,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    CASE WHEN e.hardware_device_id = 6 THEN 'device' ELSE 'derived' END,
                    l.energy_wh, l.energy_ah, 0, 0,
                    l.voltage_min, l.voltage_max, l.amperage_min, l.amperage_max,
                    l.power_min, l.power_max, l.temperature_min, l.temperature_max,
                    l.battery_min, l.battery_max, l.fan_min, l.fan_max, l.created_at, l.updated_at
                FROM hardware_power_loads_historical AS l
                JOIN hardware_energy AS e ON e.id = l.hardware_energy_id
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ");

            // 5c. Histórico de batería (#11) asignando los ciclos y Ah de batería de la tabla solar
            DB::statement("
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh_source, energy_ah_source, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges, voltage_min, voltage_max, amperage_min,
                    amperage_max, power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    hardware_device_id, 11, 1, days_operating,
                    0, 'derived', 'device', 0, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, created_at, updated_at
                FROM hardware_power_generators_historical
                WHERE hardware_energy_id = 4 AND hardware_device_id = 6
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ");
        });

        $finalReadings = DB::table('hardware_energy_readings')->count();
        $finalToday = DB::table('hardware_energy_today')->count();
        $finalHist = DB::table('hardware_energy_historical')->count();
        $batteryReadings = DB::table('hardware_energy_readings')->where('hardware_energy_id', 11)->count();

        $this->newLine();
        $this->info('¡Migración completada con éxito!');
        $this->table(
            ['Tabla Unificada', 'Registros Totales Migrados'],
            [
                ['hardware_energy_readings', number_format($finalReadings)],
                ['hardware_energy_today', number_format($finalToday)],
                ['hardware_energy_historical', number_format($finalHist)],
                ['hardware_energy_readings (Batería #11 rescatada)', number_format($batteryReadings)],
            ]
        );

        return self::SUCCESS;
    }
}
