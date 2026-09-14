<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resúmenes diarios de energía: una fila por elemento y día (UTC).
 *
 * `energy_wh_source` y `energy_ah_source` dicen, magnitud a magnitud, si el total
 * del día lo declaró el aparato (`device`) o lo sumamos de las lecturas
 * (`derived`). Sin ellas el cierre nocturno hacía `max(declarado, suma)` y
 * sustituía el contador del aparato en cuanto nuestra suma salía mayor.
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

            $table->string('energy_wh_source', 16)
                ->default('derived')
                ->comment('device = los vatios-hora del día los declara el aparato | derived = los sumamos de nuestras lecturas.');
            $table->string('energy_ah_source', 16)
                ->default('derived')
                ->comment('device = los amperios-hora del día los declara el aparato | derived = los sumamos de nuestras lecturas.');

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
