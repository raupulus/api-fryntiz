<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refactorización de hardware_energy para la arquitectura unificada.
 *
 * 1. Desvinculación de energy_systems: se elimina la FK y columna energy_system_id.
 * 2. Migración de capacidad: se convierte capacity_mah a capacity_ah (numeric 8,3).
 * 3. Supresión de capacity_wh física (pasa a ser un Accessor dinámico en Eloquent).
 * 4. Incorporación de auto_calculate_history para gobernar el cron de medianoche.
 * 5. Documentación de propósito de catálogo para rated_power_w.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy';

    public function up(): void
    {
        // 1. Añadir nueva columna capacity_ah y auto_calculate_history
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->decimal('capacity_ah', 8, 3)
                ->nullable()
                ->after('voltage_max')
                ->comment('Capacidad nominal de batería en Amperios-hora (Ah, resolución hasta 1 mAh)');

            $table->boolean('auto_calculate_history')
                ->default(true)
                ->after('rated_power_w')
                ->comment('true = el cron nocturno consolida/recalcula históricos; false = respeta contadores nativos de hardware');
        });

        // 2. Migrar datos existentes de mAh a Ah
        DB::table($this->tableName)
            ->whereNotNull('capacity_mah')
            ->update([
                'capacity_ah' => DB::raw('ROUND(capacity_mah / 1000.0, 3)'),
            ]);

        // 3. Eliminar dependencias de energy_systems y columnas obsoletas
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropIndex(['energy_system_id', 'role']);
            $table->dropForeign(['energy_system_id']);
            $table->dropColumn(['energy_system_id', 'capacity_mah', 'capacity_wh']);
        });

        // 4. Actualizar comentario explicativo de rated_power_w
        DB::statement("COMMENT ON COLUMN {$this->tableName}.rated_power_w IS 'Potencia nominal/pico de diseño de catálogo (W). Para métricas de % de rendimiento en UI y alertas.'");
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->foreignId('energy_system_id')
                ->nullable()
                ->after('energy_source_type_id')
                ->comment('Instalación a la que pertenece el elemento.')
                ->constrained('energy_systems')->nullOnDelete();

            $table->index(['energy_system_id', 'role']);

            $table->decimal('capacity_mah', 12, 2)
                ->nullable()
                ->after('voltage_max')
                ->comment('Capacidad de la batería del elemento (mAh).');

            $table->decimal('capacity_wh', 12, 2)
                ->nullable()
                ->after('capacity_mah')
                ->comment('Capacidad de la batería del elemento (Wh).');
        });

        DB::table($this->tableName)
            ->whereNotNull('capacity_ah')
            ->update([
                'capacity_mah' => DB::raw('ROUND(capacity_ah * 1000.0, 2)'),
            ]);

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['capacity_ah', 'auto_calculate_history']);
        });
    }
};
