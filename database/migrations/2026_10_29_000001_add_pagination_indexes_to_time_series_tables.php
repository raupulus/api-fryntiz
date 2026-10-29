<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade índices compuestos orientados a paginación profunda (Deferred Join /
 * Index-Only Scan) en las tablas de series temporales meteorológicas y GDACS.
 *
 * Las once tablas de sensores meteorológicos solo tenían el índice compuesto
 * `(hardware_device_id, created_at)`. Al paginar sin filtrar por estación
 * (`ORDER BY created_at DESC, id DESC`), PostgreSQL no podía aprovechar ese
 * índice y volcaba la ordenación de los millones de filas a disco temporal
 * (`external merge Disk`). Además, incluir `id` al final del índice permite
 * resolver la subconsulta de IDs del Deferred Join mediante un Index-Only Scan
 * sin tocar el heap de la tabla hasta las 25 filas finales.
 */
return new class extends Migration
{
    /**
     * Se desactiva la transacción única para que cada `CREATE INDEX` libere el
     * bloqueo sobre su tabla inmediatamente y no provoque un deadlock cruzado
     * con las ingestas IoT concurrentes de varios sensores en producción.
     */
    public $withinTransaction = false;

    /**
     * Tablas de sensores meteorológicos con patrón `(hardware_device_id, created_at, id)`.
     *
     * @var list<string>
     */
    private array $sensorTables = [
        'meteorology_temperature',
        'meteorology_humidity',
        'meteorology_pressure',
        'meteorology_light',
        'meteorology_winter',
        'meteorology_wind_direction',
        'meteorology_rain',
        'meteorology_eco2',
        'meteorology_tvoc',
        'meteorology_air_quality',
        'meteorology_lightning',
    ];

    public function up(): void
    {
        foreach ($this->sensorTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasIndex($tableName, "{$tableName}_created_at_id_idx")) {
                    $table->index(
                        ['created_at', 'id'],
                        "{$tableName}_created_at_id_idx"
                    );
                }

                if (! Schema::hasIndex($tableName, "{$tableName}_device_created_at_id_idx")) {
                    $table->index(
                        ['hardware_device_id', 'created_at', 'id'],
                        "{$tableName}_device_created_at_id_idx"
                    );
                }
            });
        }

        Schema::table('gdacs_events', function (Blueprint $table): void {
            if (! Schema::hasIndex('gdacs_events', 'gdacs_events_from_date_id_idx')) {
                $table->index(['from_date', 'id'], 'gdacs_events_from_date_id_idx');
            }
        });
    }

    public function down(): void
    {
        foreach ($this->sensorTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_created_at_id_idx");
                $table->dropIndex("{$tableName}_device_created_at_id_idx");
            });
        }

        Schema::table('gdacs_events', function (Blueprint $table): void {
            $table->dropIndex('gdacs_events_from_date_id_idx');
        });
    }
};
