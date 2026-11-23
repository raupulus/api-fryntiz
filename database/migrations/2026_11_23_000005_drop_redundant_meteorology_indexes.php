<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DROP INDEX CONCURRENTLY exige ejecutarse fuera de una transacción en PostgreSQL.
     */
    public $withinTransaction = false;

    /**
     * Las 11 tablas de sensores meteorológicos cuyo índice (hardware_device_id, created_at)
     * es prefijo izquierdo exacto y 100% redundante respecto a (hardware_device_id, created_at, id).
     *
     * @var list<string>
     */
    private const TABLES = [
        'meteorology_air_quality',
        'meteorology_eco2',
        'meteorology_humidity',
        'meteorology_light',
        'meteorology_lightning',
        'meteorology_pressure',
        'meteorology_rain',
        'meteorology_temperature',
        'meteorology_tvoc',
        'meteorology_wind_direction',
        'meteorology_winter',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$table}_hardware_device_id_created_at_index");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$table}_hardware_device_id_created_at_index ON {$table} (hardware_device_id, created_at)");
        }
    }
};
