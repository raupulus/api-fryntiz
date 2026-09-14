<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de elementos energéticos: cada fila es un papel (`generator`,
 * `battery` o `load`) que cumple algo dentro de un dispositivo medidor.
 *
 * `role` es la única fuente de verdad del papel. `sensor_position` es `NOT NULL`
 * con `0` por defecto **porque lo necesita el índice único**: en PostgreSQL dos
 * `NULL` no chocan entre sí, y con la columna nullable dos filas idénticas con el
 * canal vacío pasarían la restricción.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Asocia dispositivos que monitorizan consumo o generación de energía con sus dispositivos monitorizados');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo que hace de medidor.');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('hardware_device_monitorized_id')
                ->nullable()
                ->comment('Dispositivo cuyo consumo o generación se está midiendo.');
            $table->foreign('hardware_device_monitorized_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->foreignId('energy_source_type_id')
                ->nullable()
                ->comment('Tipo de fuente: solar, eólica, red…')
                ->constrained('energy_source_types')->nullOnDelete();

            $table->smallInteger('sensor_position')
                ->default(0)
                ->comment('Qué sensor del medidor corresponde a este elemento. 0 si sólo tiene uno.');
            $table->string('role', 16)
                ->default('load')
                ->comment('generator | load | battery.');
            $table->decimal('nominal_voltage', 8, 2)
                ->nullable()
                ->comment('Tensión nominal del elemento (V). Se usa si la medida no es plausible.');
            $table->decimal('voltage_min', 8, 2)
                ->nullable()
                ->comment('Por debajo de esto, la tensión medida se considera errónea.');
            $table->decimal('voltage_max', 8, 2)
                ->nullable()
                ->comment('Por encima de esto, la tensión medida se considera errónea.');
            $table->decimal('rated_power_w', 10, 2)
                ->nullable()
                ->comment('Potencia nominal/pico de diseño de catálogo (W). Para métricas de % de rendimiento en UI y alertas.');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Un elemento retirado deja de aceptar lecturas nuevas.');
            $table->decimal('capacity_ah', 8, 3)
                ->nullable()
                ->comment('Capacidad nominal de batería en Amperios-hora (Ah, resolución hasta 1 mAh)');
            $table->boolean('auto_calculate_history')
                ->default(true)
                ->comment('true = el cron nocturno consolida/recalcula históricos; false = respeta contadores nativos de hardware');
            $table->unsignedInteger('default_interval_seconds')
                ->default(60)
                ->comment('Segundos que se suponen entre lecturas cuando la subida no trae `duration`.');

            $table->timestamps();
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');

            $table->index(['hardware_device_id', 'sensor_position']);
            $table->unique(
                ['hardware_device_id', 'hardware_device_monitorized_id', 'role', 'sensor_position'],
                'hardware_energy_device_monitorized_role_position_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
