<?php

declare(strict_types=1);

namespace App\Console\Commands\IoT;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comando para migrar todas las lecturas históricas de las tablas legacy
 * hacia las nuevas tablas unificadas de energía (0% de pérdida).
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

        DB::statement('TRUNCATE TABLE hardware_energy_readings, hardware_energy_today, hardware_energy_historical RESTART IDENTITY CASCADE');

        DB::transaction(function () use ($solarCount) {
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
                    hardware_device_id, hardware_energy_id, voltage, amperage, power,
                    delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                    battery_voltage, battery_percentage, temperature, NULL,
                    charging_status, charging_status_label, light_status, light_brightness,
                    is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                FROM hardware_power_generators
                ORDER BY id ASC
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
                    hardware_device_id, hardware_energy_id, voltage, amperage, power,
                    delta_seconds, energy_wh, energy_ah, energy_source, voltage_source,
                    battery_voltage, battery_percentage, temperature, fan,
                    NULL, NULL, NULL, NULL,
                    is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                FROM hardware_power_loads
                ORDER BY id ASC
            ');

            if ($solarCount > 0) {
                $this->info('3/5. Descomponiendo atómicamente hardware_power_generators_solar (paneles, carga y batería)...');

                // 3a. Parte generación (elemento #4 panel FV)
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
                        battery_voltage, battery_percentage, temperature, NULL,
                        charging_status, charging_status_label, light_status, light_brightness,
                        is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                    FROM hardware_power_generators_solar
                    ORDER BY id ASC
                ');

                // 3b. Parte consumo (elemento #7 salida DC)
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
                        battery_voltage, battery_percentage, temperature, load_fan,
                        NULL, NULL, NULL, NULL,
                        is_suspicious, suspicious_reason, COALESCE(read_at, created_at), updated_at
                    FROM hardware_power_generators_solar
                    WHERE load_voltage IS NOT NULL OR load_current IS NOT NULL OR load_power IS NOT NULL
                    ORDER BY id ASC
                ");

                // 3c. Parte batería (elemento #11 banco LiFePO4)
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
                    WHERE battery_voltage IS NOT NULL OR battery_percentage IS NOT NULL
                    ORDER BY id ASC
                ");
            }

            $this->info('4/5. Traspasando resúmenes diarios (hardware_energy_today)...');
            DB::statement('
                INSERT INTO hardware_energy_today (
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh, energy_ah, voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                    fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh, energy_ah, voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                    NULL, NULL, created_at, updated_at
                FROM hardware_power_generators_today
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ');

            DB::statement('
                INSERT INTO hardware_energy_today (
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh, energy_ah, voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                    fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    hardware_device_id, hardware_energy_id, date, readings_count,
                    energy_wh, energy_ah, voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, battery_percentage_min, battery_percentage_max,
                    fan_min, fan_max, created_at, updated_at
                FROM hardware_power_loads_today
                ON CONFLICT (hardware_energy_id, date) DO NOTHING
            ');

            $this->info('5/5. Traspasando acumulados históricos (hardware_energy_historical)...');
            DB::statement('
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges, voltage_min, voltage_max, amperage_min,
                    amperage_max, power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    hardware_device_id, hardware_energy_id, 1, days_operating,
                    readings_count, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges,
                    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    NULL, NULL, created_at, updated_at
                FROM hardware_power_generators_historical
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ');

            DB::statement('
                INSERT INTO hardware_energy_historical (
                    hardware_device_id, hardware_energy_id, session_index, days_operating,
                    readings_count, energy_wh, energy_ah, number_battery_full_charges,
                    number_battery_over_discharges, voltage_min, voltage_max, amperage_min,
                    amperage_max, power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                )
                SELECT
                    hardware_device_id, hardware_energy_id, 1, days_operating,
                    readings_count, energy_wh, energy_ah, 0, 0,
                    voltage_min, voltage_max, amperage_min, amperage_max,
                    power_min, power_max, temperature_min, temperature_max,
                    battery_min, battery_max, fan_min, fan_max, created_at, updated_at
                FROM hardware_power_loads_historical
                ON CONFLICT (hardware_energy_id, session_index) DO NOTHING
            ');
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
