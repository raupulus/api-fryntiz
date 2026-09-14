<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borra las tablas del esquema viejo de energía.
 *
 * Todo lo que tenían ya está en `hardware_energy_readings`, `_today` e
 * `_historical` (comprobado el 2026-09-14 contra el volcado de producción: ni
 * una lectura, un día ni un histórico del Rover o del Sunix sin pasar). Nadie
 * escribe en ellas desde el 13/09/2026 a las 11:58 y ningún código las lee. Lo
 * único que no pasó fueron 30 lecturas de pruebas de 2023–2025 sin elemento
 * asignado, que se dan por perdidas.
 *
 * Con ellas se van sus migraciones de creación, así que también se quitan sus
 * filas de `migrations`: sin fichero no pintan nada ahí.
 *
 * **No tiene vuelta atrás.** La copia es el volcado de producción del
 * 2026-09-14.
 */
return new class extends Migration
{
    private const TABLAS = [
        'hardware_power_generators',
        'hardware_power_generators_today',
        'hardware_power_generators_historical',
        'hardware_power_generators_solar',
        'hardware_power_loads',
        'hardware_power_loads_today',
        'hardware_power_loads_historical',
    ];

    private const MIGRACIONES = [
        '2022_01_30_232819_create_hardware_power_loads_table',
        '2022_01_30_232820_create_hardware_power_loads_today_table',
        '2022_01_30_232821_create_hardware_power_loads_historical_table',
        '2022_01_30_232822_create_hardware_power_generators_table',
        '2022_01_30_232823_create_hardware_power_generators_today_table',
        '2022_01_30_232824_create_hardware_power_generators_historical_table',
        '2026_07_06_000002_create_hardware_power_generators_solar_table',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::dropIfExists($tabla);
        }

        DB::table('migrations')->whereIn('migration', self::MIGRACIONES)->delete();
    }

    public function down(): void
    {
        // Sin vuelta atrás: los datos sólo están en el volcado de producción.
    }
};
