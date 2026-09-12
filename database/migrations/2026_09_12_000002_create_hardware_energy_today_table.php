<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla unificada de resúmenes diarios de energía.
 *
 * Unifica `hardware_power_generators_today` y `hardware_power_loads_today`.
 * Almacena exactamente una fila por elemento y día del calendario. La marca de
 * última muestra agregada coincide con `updated_at` (sin columna `read_at`).
 *
 * // TODO: Las tablas legacy se conservan en frío como backup de seguridad
 * // y se eliminarán tras verificar la estabilidad en producción.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_today';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Resúmenes diarios de energía (1 fila por elemento y fecha)');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo al que pertenece la agregación diaria');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('hardware_energy_id')
                ->nullable()
                ->comment('Elemento concreto (canal/rol) al que corresponde el día');
            $table->foreign('hardware_energy_id')
                ->references('id')->on('hardware_energy')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->date('date')->comment('Fecha del día de la agregación');

            $table->integer('readings_count')
                ->default(0)
                ->comment('Número de lecturas procesadas durante el día');

            $table->decimal('energy_wh', 16, 4)
                ->default(0)
                ->comment('Total de energía acumulada en el día (Wh)');
            $table->decimal('energy_ah', 14, 4)
                ->default(0)
                ->comment('Total de amperios-hora transferidos en el día (Ah)');

            $table->decimal('voltage_min', 8, 3)
                ->nullable()
                ->comment('Tensión mínima alcanzada hoy (V)');
            $table->decimal('voltage_max', 8, 3)
                ->nullable()
                ->comment('Tensión máxima alcanzada hoy (V)');

            $table->decimal('amperage_min', 12, 6)
                ->nullable()
                ->comment('Corriente mínima alcanzada hoy (A, resolución hasta 1 µA)');
            $table->decimal('amperage_max', 12, 6)
                ->nullable()
                ->comment('Corriente máxima alcanzada hoy (A, resolución hasta 1 µA)');

            $table->decimal('power_min', 12, 4)
                ->nullable()
                ->comment('Potencia mínima alcanzada hoy (W)');
            $table->decimal('power_max', 12, 4)
                ->nullable()
                ->comment('Potencia máxima alcanzada hoy (W)');

            $table->decimal('temperature_min', 6, 3)
                ->nullable()
                ->comment('Temperatura mínima registrada hoy (°C)');
            $table->decimal('temperature_max', 6, 3)
                ->nullable()
                ->comment('Temperatura máxima registrada hoy (°C)');

            $table->decimal('battery_min', 8, 3)
                ->nullable()
                ->comment('Tensión mínima de batería registrada hoy (V)');
            $table->decimal('battery_max', 8, 3)
                ->nullable()
                ->comment('Tensión máxima de batería registrada hoy (V)');

            $table->smallInteger('battery_percentage_min')
                ->nullable()
                ->comment('SOC mínimo de batería hoy (%)');
            $table->smallInteger('battery_percentage_max')
                ->nullable()
                ->comment('SOC máximo de batería hoy (%)');

            $table->smallInteger('fan_min')
                ->nullable()
                ->comment('Velocidad/estado mínimo del ventilador hoy');
            $table->smallInteger('fan_max')
                ->nullable()
                ->comment('Velocidad/estado máximo del ventilador hoy');

            $table->timestamps();

            $table->unique(['hardware_energy_id', 'date'], 'hardware_energy_today_energy_date_unique');
            $table->index(['hardware_device_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
